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
use App\Jobs\AsyncBaiduWorkSourcesUploadJob;
use App\Model\Index\AsyncBaiduWorkSourcesUpload;
use App\Model\Index\AsyncBaiduWorkSourceUpload;
use App\Model\Index\BaiduOauth;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\User;
use App\Servers\SystemServer;

/**
 * 百度网盘通用
 * Class BaiduController
 * @package App\Http\Controllers\Api
 */
class BaiduController extends UserGuardController
{
    /**
     * 获取授权地址
     * @return mixed
     */
    public function getOauth()
    {
        $user = auth($this->guard)->user();
        $baidu = config('custom.baidu.pan');
        $redirect_uri = config('app.url').'/oauth/baidu/pan?user_id='.$user->id;
        $oAuthUrl = 'https://openapi.baidu.com/oauth/2.0/authorize?response_type=token&client_id='.$baidu['apiKey'].'&redirect_uri='.
            $redirect_uri
            .'&scope=basic,netdisk&display=mobile&state=xxx';
        $config = [
            'oAuthUrl' => $oAuthUrl,
        ];

        return $this->responseParseArray($config);
    }

    /**
     * 获取文件列表
     * @param SystemRequest $request
     * @return mixed
     */
    public function getFileList(SystemRequest $request)
    {
        $response = $this->_baiduRequest('https://pan.baidu.com/rest/2.0/xpan/file?method=list', $request->all());

        return $this->response->array($response);
    }

    /**
     * 搜索文件列表
     * @param SystemRequest $request
     * @return mixed
     */
    public function getFileSearch(SystemRequest $request)
    {
        $response = $this->_baiduRequest('https://pan.baidu.com/rest/2.0/xpan/file?method=search', $request->all());

        return $this->response->array($response);
    }

    /**
     * 网盘中下载并上传到七牛验证
     * @return \Dingo\Api\Http\Response|void
     */
    public function downAndUpQiniuVerify(SystemRequest $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            $fsids = $request->fsids;
            $user_id = auth($this->guard)->id();
            $photographer_work = User::photographer(null, $this->guard)->photographerWorks()->where(
                ['status' => 0]
            )->first();
            if (!$photographer_work) {
                \DB::rollback();//回滚事务

                return $this->response->error('作品集不存在', 500);
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
     * 网盘中下载并上传到七牛
     * @return \Dingo\Api\Http\Response|void
     */
    public function downAndUpQiniu(SystemRequest $request)
    {
        \DB::beginTransaction();//开启事务
        try {
            $fsids = $request->fsids;
            $user_id = auth($this->guard)->id();
            $photographer_work = User::photographer(null, $this->guard)->photographerWorks()->where(
                ['status' => 0]
            )->first();
            if (!$photographer_work) {
                \DB::rollback();//回滚事务
                $error = [
                    'msg' => '作品集不存在',
                    'fsids' => $fsids,
                    'log_time' => date('Y-m-d H:i:s'),
                ];
                SystemServer::filePutContents(
                    './logs/baidu_down_and_up_qiniu/error/user_id_'.$user_id.'.log',
                    json_encode($error).PHP_EOL
                );

                return $this->response->error('作品集不存在', 500);
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
                            $error = [
                                'msg' => '必须选择图片或视频',
                                'fsids' => $fsids,
                                'log_time' => date('Y-m-d H:i:s'),
                            ];
                            SystemServer::filePutContents(
                                './logs/baidu_down_and_up_qiniu/error/user_id_'.$user_id.'.log',
                                json_encode($error).PHP_EOL
                            );

                            return $this->response->error('必须选择图片或视频', 500);
                        }
                    }
                    $asyncBaiduWorkSourcesUpload = AsyncBaiduWorkSourcesUpload::create();
                    $asyncBaiduWorkSourcesUpload->user_id = $user_id;
                    $asyncBaiduWorkSourcesUpload->photographer_work_id = $photographer_work->id;
                    $asyncBaiduWorkSourcesUpload->save();
                    $sorts = [];
                    foreach ($fsids as $k => $fs_id) {
                        $sorts[$fs_id] = $k + 1;
                    }
                    foreach ($response['list'] as $k => $file) {
                        $asyncBaiduWorkSourceUpload = AsyncBaiduWorkSourceUpload::create();
                        $asyncBaiduWorkSourceUpload->async_baidu_work_sources_upload_id = $asyncBaiduWorkSourcesUpload->id;
                        $asyncBaiduWorkSourceUpload->dlink = $file['dlink'];
                        $asyncBaiduWorkSourceUpload->category = $file['category'];
                        $asyncBaiduWorkSourceUpload->size = $file['size'];
                        $asyncBaiduWorkSourceUpload->sort = $sorts[$file['fs_id']] ?? 0;
                        $asyncBaiduWorkSourceUpload->save();
                    }
                    PhotographerWorkSource::where(
                        ['photographer_work_id' => $photographer_work->id, 'status' => 200]
                    )->update(['status' => 300]);
                    \DB::commit();//提交事务
                    AsyncBaiduWorkSourcesUploadJob::dispatch($asyncBaiduWorkSourcesUpload);
                }

                return $this->response->noContent();
            } else {
                \DB::rollback();//回滚事务
                $error = [
                    'msg' => '百度网盘获取文件信息接口保存',
                    'fsids' => $fsids,
                    'response' => $response,
                    'log_time' => date('Y-m-d H:i:s'),
                ];
                SystemServer::filePutContents(
                    './logs/baidu_down_and_up_qiniu/error/user_id_'.$user_id.'.log',
                    json_encode($error).PHP_EOL
                );

                return $this->response->array($response);
            }
        } catch (\Exception $e) {
            \DB::rollback();//回滚事务
            $error = [
                'msg' => $e->getMessage(),
                'fsids' => $fsids,
                'log_time' => date('Y-m-d H:i:s'),
            ];
            SystemServer::filePutContents(
                './logs/baidu_down_and_up_qiniu/error/user_id_'.$user_id.'.log',
                json_encode($error).PHP_EOL
            );

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

    /**
     * 获取accesstoken
     * @return mixed
     */
    private function _getBaiduAccessToken()
    {
        $access_token = BaiduOauth::where(
            [
                ['user_id', '=', auth($this->guard)->id()],
                ['expired_at', '>', date('Y-m-d H:i:s')],
            ]
        )->value('access_token');
        if (!$access_token) {
            return $this->response->error('百度网盘未授权或者授权过期', 500);
        }

        return $access_token;
    }
}
