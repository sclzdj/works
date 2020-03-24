<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/21
 * Time: 15:50
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Auth\UserGuardController;
use App\Http\Requests\Index\SystemRequest;
use App\Model\Index\AsyncBaiduWorkSourceUpload;
use App\Model\Index\BaiduOauth;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\User;
use App\Servers\ErrLogServer;
use App\Servers\SystemServer;

/**
 * 百度网盘通用
 * Class BaiduController
 * @package App\Http\Controllers\Api
 */
class BaiduController extends UserGuardController
{
    /**
     * 是否授权
     * @return mixed
     */
    public function isOauth()
    {
        $oauth = 0;
        $access_token = BaiduOauth::where(
            [
                ['user_id', '=', auth($this->guard)->id()],
                ['expired_at', '>', date('Y-m-d H:i:s')],
            ]
        )->value('access_token');
        if ($access_token) {
            $oauth = 1;
        }

        return $this->responseParseArray(compact('oauth'));
    }

    /**
     * 清除授权
     * @return mixed
     */
    public function clearOauth()
    {
        \DB::beginTransaction();//开启事务
        try {
            $this->_baiduRequest('https://openapi.baidu.com/rest/2.0/passport/auth/revokeAuthorization');
            BaiduOauth::where('user_id', auth($this->guard)->id())->delete();
            \DB::commit();

            return $this->response()->noContent();
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 获取授权地址
     * @return mixed
     */
    public function getOauth()
    {
        $user = auth($this->guard)->user();
        $baidu = config('custom.baidu.pan');
        $redirect_uri = urlencode(config('app.url').'/oauth/baidu/pan');
        $oAuthUrl = 'https://openapi.baidu.com/oauth/2.0/authorize?response_type=token&client_id='.$baidu['apiKey'].'&redirect_uri='.
            $redirect_uri
            .'&scope=basic,netdisk&display=mobile&state=xxx';
        $config = [
            'oAuthUrl' => $oAuthUrl,
        ];

        return $this->responseParseArray($config);
    }

    /**
     * 获取用户信息
     * @param SystemRequest $request
     * @return mixed
     */
    public function getNasUinfo(SystemRequest $request)
    {
        $data = $request->all();
        if (isset($data['s'])) {
            unset($data['s']);
        }
        $response = $this->_baiduRequest('https://pan.baidu.com/rest/2.0/xpan/nas?method=uinfo', $data);

        return $this->response->array($response);
    }

    /**
     * 获取文件列表
     * @param SystemRequest $request
     * @return mixed
     */
    public function getFileList(SystemRequest $request)
    {
        $data = $request->all();
        if (isset($data['s'])) {
            unset($data['s']);
        }
        $response = $this->_baiduRequest('https://pan.baidu.com/rest/2.0/xpan/file?method=list', $data);

        return $this->response->array($response);
    }

    /**
     * 搜索文件列表
     * @param SystemRequest $request
     * @return mixed
     */
    public function getFileSearch(SystemRequest $request)
    {
        $data = $request->all();
        if (isset($data['s'])) {
            unset($data['s']);
        }
        $response = $this->_baiduRequest('https://pan.baidu.com/rest/2.0/xpan/file?method=search', $data);

        return $this->response->array($response);
    }

    /**
     * 网盘中下载并上传到七牛
     * @return \Dingo\Api\Http\Response|void
     */
    public function qiniuFetchPan(SystemRequest $request)
    {
        $asynchronous_task = [];
        \DB::beginTransaction();//开启事务
        try {
            $fsids = $request->fsids;
            $user_id = auth($this->guard)->id();
            if ($request->photographer_work_id) {
                $photographer_work = $this->_photographer(null, $this->guard)->photographerWorks()->where(
                    ['id' => $request->photographer_work_id, 'status' => '200']
                )->first();
            } else {
                $photographer_work = $this->_photographer(null, $this->guard)->photographerWorks()->where(
                    ['status' => 0]
                )->first();
                if (!$photographer_work) {
                    $photographer_work = PhotographerWork::create();
                    $photographer_work->photographer_id = $this->_photographer(null, $this->guard)->id;
                    $photographer_work->save();
                }
            }
            if (!$photographer_work) {
                \DB::rollback();//回滚事务

                return $this->response->error('项目不存在', 500);
            }
            $access_token = $this->_getBaiduAccessToken();
            $url = "https://pan.baidu.com/rest/2.0/xpan/multimedia?method=filemetas";
            $data['access_token'] = $access_token;
            $data['fsids'] = '['.implode(',', $fsids).']';
            $data['dlink'] = 1;
            $response = $this->_request(
                'GET',
                $url,
                $data,
                true
            );
            if ($response['errno'] === 0) {
                if (count($response['list']) > 0) {
                    foreach ($response['list'] as $file) {
                        if ($file['category'] != 3 && $file['category'] != 1) {
                            \DB::rollback();//回滚事务

                            return $this->response->error('必须选择图片或视频', 500);
                        }
                    }
                    $sorts = [];
                    foreach ($fsids as $k => $fs_id) {
                        $sorts[$fs_id] = $k + 1;
                    }
                    $photographer_work->photographerWorkSources()->where('status', 200)->update(['status' => 400]);
                    foreach ($response['list'] as $k => $file) {
                        $photographer_work_source = PhotographerWorkSource::create();
                        $photographer_work_source->photographer_work_id = $photographer_work->id;
                        if ($file['category'] == 1) {
                            $photographer_work_source->type = 'video';
                        } elseif ($file['category'] == 3) {
                            $photographer_work_source->type = 'image';
                        }
                        $photographer_work_source->origin = 'baidu_disk';
                        $photographer_work_source->sort = $sorts[$file['fs_id']] ?? 0;
                        $photographer_work_source->status = 200;
                        $photographer_work_source->save();
                        $asyncBaiduWorkSourceUpload = AsyncBaiduWorkSourceUpload::create();
                        $asyncBaiduWorkSourceUpload->photographer_work_source_id = $photographer_work_source->id;
                        $asyncBaiduWorkSourceUpload->fs_id = $file['fs_id'];
                        $asyncBaiduWorkSourceUpload->category = $file['category'];
                        $asyncBaiduWorkSourceUpload->size = $file['size'];
                        $asyncBaiduWorkSourceUpload->save();
                        if ($file['category'] == 1) {
                            $type = 'video';
                        } elseif ($file['category'] == 3) {
                            $type = 'image';
                        } else {
                            $type = 'file';
                        }
                        $asynchronous_task[] = [
                            'task_type' => 'qiniuFetchBaiduPan',
                            'asyncBaiduWorkSourceUpload_id' => $asyncBaiduWorkSourceUpload->id,
                            'type' => $type,
                            'url' => $file['dlink'].'&access_token='.$access_token,
                            'callbackurl' => config(
                                    'app.url'
                                ).'/api/notify/qiniu/fetch?async_baidu_work_source_upload_id='.$asyncBaiduWorkSourceUpload->id,
                            'asyncBaiduWorkSourceUpload' => $asyncBaiduWorkSourceUpload,
                        ];
                    }
                }
                \DB::commit();//提交事务
                foreach ($asynchronous_task as $task) {
                    if ($task['task_type'] == 'qiniuFetchBaiduPan') {
                        $qiniuFetchBaiduPan = SystemServer::qiniuFetchBaiduPan(
                            $task['asyncBaiduWorkSourceUpload_id'],
                            $task['type'],
                            $task['url'],
                            $task['callbackurl']
                        );
                        if ($qiniuFetchBaiduPan['res']['statusCode'] != 200) {
                            ErrLogServer::qiniuNotifyFetch(
                                '系统请求七牛异步远程抓取接口时失败：'.$qiniuFetchBaiduPan['res']['error'],
                                $qiniuFetchBaiduPan['res'],
                                $task['asyncBaiduWorkSourceUpload']
                            );
                        } else {
                            $qiniuFetchBaiduPan['res']['body'] = json_decode($qiniuFetchBaiduPan['res']['body'], true);
                            AsyncBaiduWorkSourceUpload::where(
                                ['id' => $qiniuFetchBaiduPan['asyncBaiduWorkSourceUpload_id']]
                            )->update(['qiniu_fetch_id' => $qiniuFetchBaiduPan['res']['body']['id']]);
                        }
                    } elseif ($task['task_type'] == 'editRunGenerateWatermark') {
                        PhotographerWorkSource::editRunGenerateWatermark(
                            $task['photographer_work_source_id'],
                            $task['edit_node']
                        );
                    } elseif ($task['task_type'] == 'error_qiniuNotifyFop') {
                        ErrLogServer::qiniuNotifyFop(
                            $task['step'],
                            $task['msg'],
                            $task['request_data'],
                            $task['photographerWorkSource'],
                            $task['res']
                        );
                    }
                }

                return $this->response->noContent();
            } else {
                \DB::rollback();//回滚事务

                return $this->response->array($response);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务

            return $this->response->error($e->getMessage(), 500);
        }
    }

    /**
     * 内置百度请求方法
     * @param $url
     * @param array $data
     * @return 返回请求结果
     */
    private function _baiduRequest($url, $data = [], $ssl = true, $headers = [])
    {
        $access_token = $this->_getBaiduAccessToken();
        $data['access_token'] = $access_token;
        $response = $this->_request('GET', $url, $data, $ssl, $headers);

        return $response;
    }
}
