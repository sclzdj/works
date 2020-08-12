<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\UserRequest;
use App\Servers\AliSendShortMessageServer;
use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
use Illuminate\Http\Request;
use App\Model\Index\DeliverWork;
use App\Model\Index\DeliverWorkFile;
use App\Model\Index\DeliverWorkObtain;
use App\Model\Index\DeliverWorkSyncPanJob;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Servers\WechatServer;
use App\Servers\BaiduServer;
use App\Jobs\SyncPan;
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
    public function createWork(Request $request)
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

        $baiduAccessToken = '';
        if ($isSyncPan) {//如果需要同步网盘，校验是否绑定百度账号
            if ($request->input("baidu_access_token", "")) {
                $baiduAccessToken = $request->input("baidu_access_token");
            } else {
                $baiduAccessToken = $this->_getBaiduAccessToken();
            }
        }

        /*
        [
            {"path":"/1.jpg","object_key":"324.jpg","etag":"dafdasfdasg","size":1234,"pic_width":1000,"pic_height":1000,"is_choice":1},
            {"path":"/文件夹/1.jpg","object_key":"324.jpg","etag":"dafdasfdasg","size":1234,"pic_width":1000,"pic_height":1000,"is_choice":0}
        ]
        */
        $fileList = json_decode($fileList, true);
        $customerPhoneList = explode(",", $customerPhoneList);

        $dirs = array();
        $choiceFileList = array();
        $fileTotalSize = 0;
        foreach ($fileList as $file) {
            if (isset($file["is_choice"]) && $file["is_choice"] == 1) {
                $choiceFileList[] = $file;
            }

            $fileTotalSize += intval($file['size']);

            $dir = File::dirname($file["path"]);
            if (empty($dir) || $dir == "/" || $dir == ".") {
                continue;
            }
            $dirs[] = $dir;
        }
        //解决3层目录的情况 TODO 多层目录需要递归处理
        foreach ($dirs as $dir) {
            if (File::dirname($dir) != '/') {
                $dirs[] = File::dirname($dir);
            }
        }
        $dirs = array_unique($dirs);

        // 获取当前时间
        $currentTime = Carbon::now();
        $obtainExpiredAt = $currentTime->addDays(14)->toDateTimeString();

        //开启事务
        try {
            DB::beginTransaction();

            //新建作品
            $work                       = new DeliverWork();
            $work->user_id              = $userId;
            $work->photographer_id      = $photographerId;
            $work->name                 = $name;
            $work->category_id          = $categoryId;
            $work->customer_industry_id = $customerIndustryId;
            $work->file_total_num       = count($fileList);
            $work->file_total_size      = $fileTotalSize;
            $work->expired_at           = $obtainExpiredAt;
            $work->save();

            //关联文件
            $workId      = $work->id;
            $insertData  = array();
            $nowDateTime = $currentTime->toDateTimeString();
            if (!empty($dirs)) {//先插入文件夹
                foreach ($dirs as $dir) {
                    $row               = array();
                    $row['work_id']    = $workId;
                    $row['dir']        = File::dirname($dir);
                    $row['name']       = File::name($dir);
                    $row['extension']  = '';
                    $row['etag']       = '';
                    $row['object_key'] = '';
                    $row['file_type']  = 0;
                    $row['size']       = 0;
                    $row['pic_width']  = 0;
                    $row['pic_height'] = 0;
                    $row['is_dir']     = 1;
                    $row['is_choice']  = 0;
                    $row['created_at'] = $nowDateTime;
                    $row['updated_at'] = $nowDateTime;

                    $insertData[] = $row;
                }
            }
            foreach ($fileList as $file) {
                $path = $file["path"];

                $row               = array();
                $row['work_id']    = $workId;
                $row['dir']        = File::dirname($path);
                $row['name']       = File::name($path);
                $row['extension']  = File::extension($path);
                $row['etag']       = $file['etag'] ?? '';
                $row['object_key'] = $file['object_key'] ?? '';
                $row['file_type']  = 1;//图片
                $row['size']       = $file['size'] ?? 0;
                $row['pic_width']  = $file['pic_width'] ?? 0;
                $row['pic_height'] = $file['pic_height'] ?? 0;
                $row['is_dir']     = 0;
                $row['is_choice']  = $file['is_choice'] ?? 0;
                $row['created_at'] = $nowDateTime;
                $row['updated_at'] = $nowDateTime;

                $insertData[] = $row;
            }
            DeliverWorkFile::insert($insertData);

            //增加提取记录
            $insertData = array();
            foreach ($customerPhoneList as $phone) {
                $row                    = array();
                $row['work_id']         = $workId;
                $row['phone']           = $phone;
                $row['code']            = $phone;
                $row['expired_at']      = $obtainExpiredAt;
                $row['created_at']      = $nowDateTime;
                $row['updated_at']      = $nowDateTime;

                $insertData[] = $row;
            }
            DeliverWorkObtain::insert($insertData);

            //精选作品同步到小程序云作品表
            if (!empty($choiceFileList)) {
                //作品表
                $res = PhotographerWork::create([
                    'photographer_id'                        => $photographerId,
                    'photographer_work_customer_industry_id' => $customerIndustryId,
                    'name'                                   => $name,
                    'customer_name'                          => $name,
                    'sheets_number'                          => count($choiceFileList),
                    'photographer_work_category_id'          => $categoryId,
                    'status'                                 => 200
                ]);
                $photographerWorkId = $res->id;

                //资源表
                $insertData = array();
                foreach ($choiceFileList as $file) {
                    $row                         = array();
                    $row["photographer_work_id"] = $photographerWorkId;
                    $row["key"]                  = $file['object_key'];
                    $row["url"]                  = $this->getQiniuUrl($file['object_key']);
                    $row["size"]                 = $file['size'];
                    $row["type"]                 = "image";
                    $row["width"]                = $file['pic_width'];
                    $row["height"]               = $file['pic_height'];
                    $row["height"]               = $file['pic_height'];
                    $row["status"]               = 200;
                    $row['created_at'] = $nowDateTime;
                    $row['updated_at'] = $nowDateTime;

                    $insertData[] = $row;
                }
                PhotographerWorkSource::insert($insertData);
            }

            //事务提交
            DB::commit();
        } catch(\Illuminate\Database\QueryException $ex) {
            DB::rollback(); //回滚事务

            Log::warning("db transaction failed, error:" . $ex->getMessage());
            return $this->response->errorInternal("db error");
        }

        //补充小程序作品的小程序二维码，不在事务里执行，该操作会有http请求导致比较慢，可能会阻塞数据库，TODO：如果导致接口较慢，加入消息队列里异步执行
        $res = $this->updatePhotographerWorkXacode($photographerWorkId);
        if (!$res) {
            Log::warning("updatePhotographerWorkXacode failed");
            return $this->response->errorInternal("updatePhotographerWorkXacode failed");
        }

        //精选作品文件处理
        $res = $this->photographerWorkSourceDeal($photographerWorkId, $request);
        if ($res === false) {
            Log::warning("photographerWorkSourceDeal failed");
            return $this->response->error("photographerWorkSourceDeal failed", 400);
        }

        //发送短信
        $res = $this->sendObtainSms($customerPhoneList);
        if ($res === false) {
            Log::warning("sendObtainSms failed");
            return $this->response->error("sendObtainSms failed", 400);
        }

        //同步文件到网盘
        if ($isSyncPan) {
            $res = $this->syncPan($baiduAccessToken, $workId);
            if ($res === false){
                Log::warning("syncPan failed");
                return $this->response->error("sync to pan failed, check your pan qutoa.", 400);
            }
        }

        return $this->response->noContent();
    }

    /**
     * 获取作品列表
     * @return mixed
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/25
     */
    public function getWorks(Request $request)
    {
        $data = array();
        $userId = auth($this->guard)->id();
        $wheres = array();
        $wheres["user_id"] = $userId;
        $isDownload = $request->input("is_download", -1);
        if ($isDownload != -1) {
            $wheres["is_download"] = intval($isDownload);
        }
        $res = DeliverWork::where($wheres)->orderBy('id', 'desc')->paginate($request->input('per_page', 10));
        $items = $res->items();
        $data['list'] = (array)$items;
        $data['total_count'] = $res->total();
        $data['per_page'] = $res->perPage();
        $data['current_page'] = $res->currentPage();
        $data['total_page'] = ceil($res->total() / $res->perPage());

        return $this->responseParseArray($data);
    }

    /**
     * 获取作品详情
     * @return mixed
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/25
     */
    public function getWork(Request $request)
    {
        $workId = $request->route("id");
        if (empty($workId)) {
            Log::warning("param error");
            return $this->response->error("param error", 400);
        }

        //验证该作品是否是当前用户的
        $userId = auth($this->guard)->id();
        $work = DeliverWork::find($workId);
        if ($userId != $work->user_id) {
            Log::warning("this user not has permission");
            return $this->response->error("this user not has permission", 403);
        }

        $res = DeliverWork::find($workId);
        $data = $res->toArray();

        $res = DeliverWorkFile::where(['work_id' => $workId, 'dir' => '/'])->get();
        $data['file_list'] = $res->toArray();

        return $this->responseParseArray($data);
    }

    /**
     * 删除作品
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     * @throws \Throwable
     * @author jsyzchenchen@gmail.com
     * @date 2020/08/02
     */
    public function deleteWork(Request $request)
    {
        $workId = $request->route("id");
        if (empty($workId)) {
            Log::warning("param error");
            return $this->response->error("param error", 400);
        }

        //验证该作品是否是当前用户的
        $userId = auth($this->guard)->id();
        $work = DeliverWork::find($workId);
        if ($userId != $work->user_id) {
            Log::warning("this user not has permission");
            return $this->response->error("this user not has permission", 403);
        }

        //删除作品和作品文件
        DB::transaction(function () use ($workId) {
            //删除作品
            DeliverWork::destroy($workId);

            //删除作品文件
            DeliverWorkFile::where("work_id", $workId)->delete();
        });

        return $this->response->noContent();
    }

    /**
     * 根据文件夹获取作品文件列表
     * @param Request $request
     * @return mixed|void
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/25
     */
    public function getWorkFileList(Request $request)
    {
        $data = array();
        $workId = $request->input('work_id');
        if (empty($workId)) {
            Log::warning("param error");
            return $this->response->error("param error", 400);
        }
        $dir = $request->input('dir', '/');
        $res = DeliverWorkFile::where(['work_id' => $workId, 'dir' => $dir])->get();
        $data['file_list'] = $res->toArray();

        return $this->responseParseArray($data);
    }

    /**
     * 通过提取码获取作品
     * @param Request $request
     * @return mixed|void
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/25
     */
    public function getWorksByObtainCode(Request $request)
    {
        $data = array();

        $code = $request->input("code", "");
        if (empty($code)) {
            Log::warning("param error");
            return $this->response->error("param error", 400);
        }

        $currentDateTime = Carbon::now()->toDateTimeString();

        //获取可以提取的作品
        $deliverWorkObtains = DeliverWorkObtain::where("code", $code)->where("expired_at", ">=", $currentDateTime)->get();
        $workIds = array();
        foreach ($deliverWorkObtains as $row) {
            $workIds[] = $row->work_id;
        }
        if (empty($workIds)) {//没有可提取的作品或者提取码已过期
            return $this->responseParseArray($data);
        }
        //获取作品列表详情
        $res = DeliverWork::whereIn("id", $workIds)->get();
        if (!$res) {
            Log::warning("DeliverWork get failed");
            return $this->response->errorInternal("db error");
        }
        $data = $res->toArray();

        //获取作品文件列表
        $res = DeliverWorkFile::whereIn("work_id", $workIds)->get();
        if (!$res) {
            Log::warning("DeliverWorkFile get failed");
            return $this->response->errorInternal("db error");
        }
        $workFileList = array();
        foreach ($res as $file) {
            $workId = $file['work_id'];
            $workFileList[$workId][] = $file;
        }

        foreach ($data as $key => $row) {
            $workId = $row['id'];
            $row['file_list'] = $workFileList[$workId];

            $data[$key] = $row;
        }

        return $this->responseParseArray($data);
    }

    /**
     * 提取到百度网盘
     * @param Request $request
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/26
     */
    public function obtainToPan(Request $request)
    {
        $data = array();

        $obtainCode = $request->input("obtain_code", "");
        $baiduOauthCode = $request->input("baidu_oauth_code", "");
        $baiduOauthRedirectUri = $request->input("baidu_oauth_redirect_uri", "");
        $workId = $request->input("work_id", 0);
        if (empty($obtainCode) || empty($baiduOauthCode) || empty($workId)) {
            Log::warning("param error");
            return $this->response->error("param error", 400);
        }

        //根据code获取百度access_token
        $accessToken = BaiduServer::getAccessTokenByCode($baiduOauthCode, $baiduOauthRedirectUri);
        if (!$accessToken) {
            Log::warning("getAccessTokenByCode failed.");
            return $this->response->error("get baidu accessToken failed", 400);
        }
        $accessToken = "121.09328b96d4c84120efce9a518d2229fa.YDNekqyLWXmjQ-jb5ESdais69mUYXpfNwdGU9gL.I6EPaQ";
        $workId = 9;

        $currentDateTime = Carbon::now()->toDateTimeString();

        //获取可以提取的作品
        $res = DeliverWorkObtain::where("work_id", $workId)->where("code", $obtainCode)->where("expired_at", ">=", $currentDateTime)->first();
        if (is_null($res)) {
            Log::warning("this user not has permission");
            return $this->response->error("this user not has permission", 403);
        }
        $obtainId = $res->id;

        //同步到网盘
        $res = $this->syncPan($workId, $accessToken, 1, $obtainId);
        if ($res === false){
            return $this->response->error("sync to pan failed, check your pan qutoa.", 400);
        }

        return $this->response->noContent();
    }

    /**
     * 提取到本地，即下载
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     * @author jsyzchenchen@gmail.com
     * @date 2020/08/02
     */
    public function obtainToLocal(Request $request)
    {
        $obtainCode = $request->input("obtain_code", "");
        $workId = $request->input("work_id", 0);

        if (empty($obtainCode) || empty($workId)) {
            Log::warning("param error");
            return $this->response->error("param error", 400);
        }

        //获取可以提取的作品
        $currentDateTime = Carbon::now()->toDateTimeString();
        $res = DeliverWorkObtain::where("work_id", $workId)->where("code", $obtainCode)->where("expired_at", ">=", $currentDateTime)->first();
        if (is_null($res)) {
            Log::warning("this user not has permission");
            return $this->response->error("this user not has permission", 403);
        }
        $obtainId = $res->id;

        //更改提取表和作品表
        //更改提取表是否同步到网盘字段
        DeliverWorkObtain::where('id', $obtainId)->update(['status' => 1, 'is_download' => 1]);
        //修改作品表的是否已下载和下载次数
        DeliverWork::where('id', $workId)->increment('download_num', 1, ['is_download' => 1]);

        return $this->response->noContent();
    }

    /**
     * 短信提醒客户提取
     * @author jsyzchenchen@gmail.com
     * @date 2020/08/01
     */
    public function obtainRemindBySms(Request $request)
    {
        $workId = $request->input("work_id", 0);
        if (empty($workId)) {
            Log::warning("param error");
            return $this->response->error("param error", 400);
        }

        //验证该作品是否是当前用户的
        $userId = auth($this->guard)->id();
        $work = DeliverWork::find($workId);
        if ($userId != $work->user_id) {
            Log::warning("this user not has permission");
            return $this->response->error("this user not has permission", 403);
        }

        //获取提取表里未提取作品的手机号
        $noObtains = DeliverWorkObtain::where(["work_id" => $workId, 'status' => 0])->get();
        if (!$noObtains->isEmpty()) {
            $phoneList = array();
            foreach ($noObtains as $obtain) {
                $phoneList[] = $obtain->phone;
            }

            $res = $this->sendObtainSms($phoneList);
            if ($res === false) {
                Log::warning("sms send failed");
                return $this->response->error("param error", 400);
            }
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
    private function sendObtainSms($customerPhoneList)
    {
        $third_type = config('custom.send_short_message.third_type');
        $TemplateCodes = config('custom.send_short_message.' . $third_type . '.TemplateCodes');

        //发送短信
        foreach ($customerPhoneList as $phoneNumber) {
            if ($third_type == 'ali') {
                AliSendShortMessageServer::quickSendSms(
                    $phoneNumber,
                    $TemplateCodes,
                    'deliver_work_obtain',
                    [
                        'name' => '提取作品提醒',
                        'code' => mt_rand(1000, 9999),
                    ]
                );
            }
        }

        return true;
    }

    /**
     * 补充小程序作品的小程序二维码
     * @param $photographerWorkId
     * @return \Dingo\Api\Http\Response\Factory|bool
     * @throws \Exception
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/18
     */
    private function updatePhotographerWorkXacode($photographerWorkId)
    {
        $scene = '1/' . $photographerWorkId;
        $xacode_res = WechatServer::generateXacodes($scene);
        if ($xacode_res['code'] != 200) {
            Log::warning("WechatServer::generateXacodes failed, photographerWorkId[{$photographerWorkId}], msg:" . $xacode_res['msg']);
            return false;
        }
        $updateData = array();
        $updateData['xacode'] = $xacode_res['xacode'];
        $updateData['xacode_hyaline'] = $xacode_res['xacode_hyaline'];
        $res = PhotographerWork::where('id', $photographerWorkId)->update($updateData);
        if (!$res) {
            Log::warning("PhotographerWork update failed");
        }
        return $res;
    }

    /**
     * 摄影师作品处理
     * @param $photographerWorkId
     * @param $request
     * @return bool
     * @author jsyzchenchen@gmail.com
     * @date 2020/08/02
     */
    private function photographerWorkSourceDeal($photographerWorkId, $request)
    {
        $photographerWorkSources = PhotographerWorkSource::where('photographer_work_id', $photographerWorkId)->get();

        $asynchronousTask = array();
        foreach ($photographerWorkSources as $photographerWorkSource) {
            $fops = ["imageMogr2/auto-orient/thumbnail/1200x|imageMogr2/auto-orient/colorspace/srgb|imageslim"];
            $bucket = 'zuopin';
            $asynchronousTask[] = [
                'task_type' => 'qiniuPfop',
                'bucket' => $bucket,
                'key' => $photographerWorkSource->key,
                'photographer_work_source_id' => $photographerWorkSource->id,
                'fops' => $fops,
                'pipeline' => null,
                'notifyUrl' => config(
                        'app.url'
                    ).'/api/notify/qiniu/fopDeal?photographer_work_source_id=' . $photographerWorkSource->id,
                'useHTTPS' => true,
                'error_step' => '处理图片持久请求',
                'error_msg' => '七牛持久化接口返回错误信息',
                'error_request_data' => $request->all(),
                'error_photographerWorkSource' => null,
            ];
        }

        foreach ($asynchronousTask as $task) {
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
                    PhotographerWorkSource::find($task['photographer_work_source_id']),
                    $qrst['err']
                );
            }
        }

        return true;
    }

    /**
     * 同步作品的文件到网盘
     * @param $workId int 作品ID
     * @param $workName string 作品名称
     * @param $accessToken string 百度的accessToken
     * @param $operatorType int 任务发起人类型 1 提取人, 2 作者
     * @param $obtainId int 提取ID
     * @return bool|string
     * @author jsyzchenchen@gmail.com
     * @date 2020/07/18
     */
    private function syncPan($workId, $accessToken, $operatorType = 2, $obtainId = 0)
    {
        //获取文件总大小
        $work = DeliverWork::find($workId);
        $fileTotalSize = $work->file_total_size;
        $workName = $work->name;

        //查询用户的空间容量是否足够
        $quota = baiduServer::getQuotaInfo($accessToken);
        if (!$quota) {
            return false;
        }
        $quotaFree = $quota['total'] - $quota['used'];
        if ($fileTotalSize > $quotaFree) {
            Log::warning("file zise gt user free quota");
            return false;
        }

        Log::info("quota free:" . $quotaFree);

        //创建同步任务的记录
        $baiduPanConfig = config('custom.baidu.pan');
        $syncPanJob = new DeliverWorkSyncPanJob();
        $syncPanJob->work_id = $workId;
        $syncPanJob->dir = $baiduPanConfig["appDir"] . '/' . $workName;
        $syncPanJob->access_token = $accessToken;
        $syncPanJob->operator_type = $operatorType;
        $syncPanJob->obtain_id = $obtainId;
        $syncPanJob->save();

        //加入异步队列
        SyncPan::dispatch($syncPanJob)->onConnection('redis')->onQueue('sync_pan');

        Log::info("SyncPan dispatch success, workID[{$workId}], obtainId[{$obtainId}]");

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
