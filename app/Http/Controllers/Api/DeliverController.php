<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\UserRequest;
use App\Model\Index\DeliverWork;
use App\Model\Index\DeliverWorkFile;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
use App\Servers\WechatServer;
use Log;
use DB;
use File;
use Illuminate\Support\Carbon;

/**
 * 交付助手控制器
 * @package App\Http\Controllers\Api
 * @author jsyzchenchen@gmail.com
 * @date 2020/07/18
 */
class DeliverController extends UserGuardController
{
    /**
     * 新建作品
     * @param UserRequest $request
     * @return \Dingo\Api\Http\Response
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/18
     */
    public function createWork(UserRequest $request)
    {
        //验证参数
        $name = $request->input("name", "");
        $categoryId = $request->input("category_id", 0);
        $customerIndustryId = $request->input("customer_industry_id", 0);
        $customerPhoneList = $request->input("customer_phone_list", "");
        $fileList = $request->input("file_list", "");
        $isSyncPan = $request->input("is_sync_pan", 0);
        if (empty($name) || empty($categoryId) || empty($customerIndustryId) || empty($customerPhoneList) || empty($fileList)) {
            Log::warning("param error");
            return $this->response->error("param error", 400);
        }
        $user = auth($this->guard)->user();
        $userId = $user->id;
        $photographerId = $user->photographer_id;
        if ($user->is_formal_photographer == 0) {
            Log::warning("this user is not a formal photographer");
            return $this->response->error("this user is not a formal photographer", 403);
        }

        //[{"path":"/1.jpg","object_key":"324.jpg","etag":"dafdasfdasg","size":1234,"pic_width":1000,"pic_height":1000,"is_choice":1},{"path":"/文件夹/1.jpg","object_key":"324.jpg","etag":"dafdasfdasg","size":1234,"pic_width":1000,"pic_height":1000,"is_choice":0}]
        $fileList = json_decode($fileList, true);
        $customerPhoneList = explode(",", $customerPhoneList);

        $dirs = array();
        $choiceFileList = array();
        foreach ($fileList as $file) {
            if (isset($file["is_choice"]) && $file["is_choice"] == 1) {
                $choiceFileList[] = $file;
            }

            $dir = File::dirname($file["path"]);
            if (empty($dir) || $dir == "/") {
                continue;
            }
            $dirs[] = $dir;
        }
        $dirs = array_unique($dirs);

        //开启事务
        DB::beginTransaction();
        //新建作品
        $work = New DeliverWork();
        $work->user_id = $userId;
        $work->photographer_id = $photographerId;
        $work->name = $name;
        $work->category_id = $categoryId;
        $work->customer_industry_id = $customerIndustryId;
        $res = $work->save();
        if (!$res) {
            DB::rollBack();
            Log::warning("db exec failed");
            return $this->response->errorInternal("db error");
        }

        //关联文件
        $workId = $work->id;

        $insertData = array();
        $nowDateTime = Carbon::now()->toDateTimeString();
        if (!empty($dirs)) {//先插入文件夹
            foreach ($dirs as $dir) {
                $row = array();
                $row['work_id'] = $workId;
                $row['dir'] = File::dirname($dir);
                $row['name'] = File::name($dir);
                $row['is_dir'] = 1;
                $row['created_at'] = $nowDateTime;
                $row['updated_at'] = $nowDateTime;

                $insertData[] = $row;
            }
        }
        foreach ($fileList as $file) {
            $path = $file["path"];

            $row = array();
            $row['work_id'] = $workId;
            $row['dir'] = File::dirname($path);
            $row['name'] = File::name($path);
            $row['extension'] = File::extension($path);
            $row['etag'] = $file['etag'];
            $row['object_key'] = $file['object_key'];
            $row['size'] = $file['size'];
            $row['pic_width'] = $file['pic_width'];
            $row['pic_height'] = $file['pic_height'];
            $row['is_dir'] = 0;
            $row['is_choice'] = $file['is_choice'];
            $row['created_at'] = $nowDateTime;
            $row['updated_at'] = $nowDateTime;

            $insertData[] = $row;
        }
        $res = DeliverWorkFile::insert($insertData);
        if (!$res) {
            DB::rollBack();
            Log::warning("DeliverWorkFile::insert failed");
            return $this->response->errorInternal("db error");
        }

        //精选作品同步到小程序云作品表
        if (!empty($choiceFileList)) {
            //作品表
            $res = PhotographerWork::create([
                'photographer_id' => $photographerId,
                'photographer_work_customer_industry_id' => $customerIndustryId,
                'sheets_number' => count($choiceFileList),
                'photographer_work_category_id' => $categoryId,
                'status' => 200
            ]);
            if (!$res) {
                DB::rollBack();
                Log::warning("PhotographerWork::create failed");
                return $this->response->errorInternal("db error");
            }
            $photographerWorkId = $res->id;

            //资源表
            $insertData = array();
            foreach ($choiceFileList as $file) {
                $row = array();
                $row["photographer_work_id"] = $photographerWorkId;
                $row["key"] = $file['object_key'];
                $row["url"] = $this->getQiniuUrl($file['object_key']);
                $row["size"] = $file['size'];
                $row["width"] = $file['pic_width'];
                $row["height"] = $file['pic_height'];
                $row["height"] = $file['pic_height'];
                $row["status"] = 200;

                $insertData[] = $row;
            }
            $res = PhotographerWorkSource::insert($insertData);
            if (!$res) {
                DB::rollBack();
                Log::warning("PhotographerWorkSource::insert failed");
                return $this->response->errorInternal("db error");
            }
        }

        //事务提交
        DB::commit();

        //补充小程序作品的小程序二维码，不在事务里执行，该操作会有http请求导致比较慢，可能会阻塞数据库，TODO：如果导致接口较慢，加入消息队列里异步执行
        $this->updatePhotographerWorkXacode($photographerWorkId);

        //发送短信
        $this->sendSms($workId, $customerPhoneList);

        //同步文件到网盘
        if ($isSyncPan) {
            $this->syncPan($userId, $work->name, $fileList);
        }

        return $this->response->noContent();
    }

    /**
     * 发送短信
     * @param $customerPhoneList
     * @return bool
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/18
     */
    private function sendSms($workId, $customerPhoneList)
    {
        //发送短信
        foreach ($customerPhoneList as $phone) {

        }

        return true;
    }

    /**
     * 补充小程序作品的小程序二维码
     * @param $photographerWorkId
     * @return \Dingo\Api\Http\Response\Factory
     * @throws \Exception
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/18
     */
    private function updatePhotographerWorkXacode($photographerWorkId)
    {
        $photographerWork = PhotographerWork::find($photographerWorkId);
        $scene = '1/' . $photographerWorkId;
        $xacode_res = WechatServer::generateXacodes($scene);
        if ($xacode_res['code'] != 200) {
            DB::rollback();//回滚事务

            return $this->response($xacode_res['msg'], $xacode_res['code']);
        }
        $photographerWork->xacode = $xacode_res['xacode'];
        $photographerWork->xacode_hyaline = $xacode_res['xacode_hyaline'];
        $res = $photographerWork->save();
        return $res;
    }

    /**
     * 异步处理七牛的图片
     * @param $fileList
     * @return bool
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/18
     */
    private function asyncHandleQiniuImages($photographerWorkId, $fileList)
    {
        //精选图片处理
        $sourceList = PhotographerWorkSource::where("photographer_work_id", $photographerWorkId)->get();
        $fops = ["imageMogr2/auto-orient/thumbnail/1200x|imageMogr2/auto-orient/colorspace/srgb|imageslim"];
        $bucket = 'zuopin';

        foreach ($sourceList as $source) {
            $task = [
                'task_type' => 'qiniuPfop',
                'bucket' => $bucket,
                'key' => $source->key,
                'fops' => $fops,
                'pipeline' => null,
                'notifyUrl' => config('app.url') . '/api/notify/qiniu/fopDeal?photographer_work_source_id=' . $source->id,
                'useHTTPS' => true,
                'error_step' => '处理图片持久请求',
                'error_msg' => '七牛持久化接口返回错误信息',
                'error_request_data' => array(),
                'error_photographerWorkSource' => $source,
            ];

            $qrst = SystemServer::qiniuPfop(
                $task['bucket'],
                $task['key'],
                $task['fops'],
                $task['pipeline'],
                $task['notifyUrl'],
                $task['useHTTPS']
            );
            if ($qrst['err']) {
                ErrLogServer::qiniuNotifyFop(
                    $task['error_step'],
                    $task['error_msg'],
                    $task['error_request_data'],
                    $task['error_photographerWorkSource'],
                    $qrst['err']
                );
            }
        }

        //其他图片处理

        return true;
    }

    /**
     * 同步文件到网盘
     * @param $userId
     * @param $workName
     * @param $fileList
     * @return bool
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/18
     */
    private function syncPan($userId, $workName, $fileList)
    {
        return true;
    }

    /**
     * 获取七牛的文件地址
     * @param $key
     * @return string
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/18
     */
    private function getQiniuUrl($key)
    {
        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';
        $url = $domain . '/' . $key;
        return $url;
    }
}
