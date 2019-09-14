<?php

namespace App\Http\Controllers\Api\Notify;

use App\Http\Controllers\Api\BaseController;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\User;
use App\Servers\SystemServer;

/**
 * 七牛相关控制器
 * Class QiniuController
 * @package App\Http\Controllers\Api\Notify
 */
class QiniuController extends BaseController
{
    /**
     * 七牛持久化异步通知
     */
    public function fop()
    {
        $log_filename = 'logs/qiniu_fop_error/'.date('Y-m-d').'/'.date('H').'.log';

        $request_data = \Request::all();
        $photographerWorkSource = PhotographerWorkSource::where(
            ['id' => $request_data['photographer_work_source_id']]
        )->first();
        if (!$photographerWorkSource) {
            $error = [];
            $error['log_time'] = date('i:s');
            $error['step'] = $request_data['step'];
            $error['msg'] = '作品集资源不存在';
            $error['response'] = $request_data;
            SystemServer::filePutContents(
                $log_filename,
                json_encode($error).PHP_EOL
            );

            return;
        }
        $photographerWork = PhotographerWork::where(['id' => $photographerWorkSource->photographer_work_id])->first();
        if (!$photographerWork) {
            $error = [];
            $error['log_time'] = date('i:s');
            $error['step'] = $request_data['step'];
            $error['msg'] = '作品集不存在';
            $error['response'] = $request_data;
            SystemServer::filePutContents(
                $log_filename,
                json_encode($error).PHP_EOL
            );

            return;
        }
        $photographer = Photographer::where(['id' => $photographerWork->photographer_id])->first();
        if (!$photographer) {
            $error = [];
            $error['log_time'] = date('i:s');
            $error['step'] = $request_data['step'];
            $error['msg'] = '摄影师不存在';
            $error['response'] = $request_data;
            SystemServer::filePutContents(
                $log_filename,
                json_encode($error).PHP_EOL
            );

            return;
        }
        $user = User::where(['photographer_id' => $photographerWork->photographer_id])->first();
        if (!$user) {
            $error = [];
            $error['log_time'] = date('i:s');
            $error['step'] = $request_data['step'];
            $error['msg'] = '用户不存在';
            $error['response'] = $request_data;
            SystemServer::filePutContents(
                $log_filename,
                json_encode($error).PHP_EOL
            );

            return;
        }
        try {
            if ($request_data['code'] == 0) {
                if (isset($request_data['items'][0]) && $request_data['items'][0]['code'] == 0) {
                    $bucket = 'zuopin';
                    $buckets = config('custom.qiniu.buckets');
                    $domain = $buckets[$bucket]['domain'] ?? '';
                    if ($request_data['step'] == 1) {
                        $photographerWorkSource->deal_key = $request_data['items'][0]['key'];
                        $photographerWorkSource->deal_url = $domain.'/'.$request_data['items'][0]['key'];
                        $photographerWorkSource->rich_key = $request_data['items'][0]['key'];
                        $photographerWorkSource->rich_url = $domain.'/'.$request_data['items'][0]['key'];
                        $photographerWorkSource->save();
                        if ($photographerWorkSource->type == 'image') {
                            $response = SystemServer::request('GET', $photographerWorkSource->deal_url.'?imageInfo');
                            if ($response['code'] == 200) {
                                if (isset($response['data']['code']) && $response['data']['code'] != 200) {
                                    $error = [];
                                    $error['log_time'] = date('i:s');
                                    $error['step'] = $request_data['step'];
                                    $error['msg'] = '请求图片信息接口返回错误信息';
                                    $error['response'] = $response['data'];
                                    SystemServer::filePutContents(
                                        $log_filename,
                                        json_encode($error).PHP_EOL
                                    );
                                    $photographerWorkSource->status = 500;
                                    $photographerWorkSource->save();

                                    return;
                                } else {
                                    $photographerWorkSource->deal_size = $response['data']['size'];
                                    $photographerWorkSource->rich_size = $response['data']['size'];
                                    $photographerWorkSource->save();
                                    $water1_image = \Qiniu\base64_urlSafeEncode($photographerWorkSource->deal_url);
                                    $water2_image = \Qiniu\base64_urlSafeEncode(config('app.url').'/'.$user->xacode);
                                    $water2_image_dy = $response['data']['height'] - 100;
                                    $water3_text = \Qiniu\base64_urlSafeEncode("我是摄影师".$photographer->name);
                                    $water3_text_dy = $response['data']['height'] + 115;
                                    $water4_text = \Qiniu\base64_urlSafeEncode("微信扫一扫，看我的全部作品");
                                    $water4_text_dy = $response['data']['height'] + 140;
                                    $fops = ["imageMogr2/auto-orient/crop/{$response['data']['width']}x".($response['data']['height'] + 185)."|watermark/3/image/{$water1_image}/gravity/North/dx/0/dy/0/image/{$water2_image}/gravity/North/dx/0/dy/{$water2_image_dy}/text/{$water3_text}/fontsize/400/gravity/North/dx/0/dy/{$water3_text_dy}/text/{$water4_text}/fontsize/400/gravity/North/dx/0/dy/{$water4_text_dy}|imageslim"];
                                    $qrst = SystemServer::qiniuPfop(
                                        $bucket,
                                        config('custom.qiniu.crop_work_source_image_bg'),
                                        $fops,
                                        null,
                                        config(
                                            'app.url'
                                        ).'/api/notify/qiniu/fop?photographer_work_source_id='.$photographerWorkSource->id.'&step=2&width='.$response['data']['width'].'&height='.$response['data']['height'],
                                        true
                                    );
                                    if (!empty($qrst['err'])) {
                                        $error = [];
                                        $error['log_time'] = date('i:s');
                                        $error['step'] = $request_data['step'];
                                        $error['msg'] = '持久化请求失败：'.json_encode($qrst['err']);
                                        SystemServer::filePutContents(
                                            $log_filename,
                                            json_encode($error).PHP_EOL
                                        );
                                        $photographerWorkSource->status = 500;
                                        $photographerWorkSource->save();
                                    }
                                }
                            } else {
                                $error = [];
                                $error['log_time'] = date('i:s');
                                $error['step'] = $request_data['step'];
                                $error['msg'] = '请求图片信息接口失败：'.$response['msg'];
                                SystemServer::filePutContents(
                                    $log_filename,
                                    json_encode($error).PHP_EOL
                                );
                                $photographerWorkSource->status = 500;
                                $photographerWorkSource->save();

                                return;
                            }
                        } elseif ($photographerWorkSource->type == 'video') {

                        }
                    } elseif ($request_data['step'] == 2) {
                        if ($photographerWorkSource->type == 'image') {
                            $photographerWorkSource->rich_key = $request_data['items'][0]['key'];
                            $photographerWorkSource->rich_url = $domain.'/'.$request_data['items'][0]['key'];
                            $photographerWorkSource->save();
                            $response = SystemServer::request('GET', $photographerWorkSource->rich_url.'?imageInfo');
                            if ($response['code'] == 200) {
                                if (isset($response['data']['code']) && $response['data']['code'] != 200) {
                                    $error = [];
                                    $error['log_time'] = date('i:s');
                                    $error['step'] = $request_data['step'];
                                    $error['msg'] = '请求图片信息接口返回错误信息';
                                    $error['response'] = $response['data'];
                                    SystemServer::filePutContents(
                                        $log_filename,
                                        json_encode($error).PHP_EOL
                                    );
                                    $photographerWorkSource->status = 500;
                                    $photographerWorkSource->save();

                                    return;
                                } else {
                                    $photographerWorkSource->rich_size = $response['data']['size'];
                                    $photographerWorkSource->save();
                                }
                            } else {
                                $error = [];
                                $error['log_time'] = date('i:s');
                                $error['step'] = $request_data['step'];
                                $error['msg'] = '请求图片信息接口失败：'.$response['msg'];
                                SystemServer::filePutContents(
                                    $log_filename,
                                    json_encode($error).PHP_EOL
                                );
                                $photographerWorkSource->status = 500;
                                $photographerWorkSource->save();

                                return;
                            }
                        } elseif ($photographerWorkSource->type == 'video') {

                        }
                    } elseif ($request_data['step'] == 3) {

                    }
                } else {
                    $error = [];
                    $error['log_time'] = date('i:s');
                    $error['step'] = $request_data['step'];
                    $error['msg'] = '七牛接口报错或返回信息不存在';
                    $error['response'] = $request_data;
                    SystemServer::filePutContents(
                        $log_filename,
                        json_encode($error).PHP_EOL
                    );
                    $photographerWorkSource->status = 500;
                    $photographerWorkSource->save();

                    return;
                }
            } else {
                $error = [];
                $error['log_time'] = date('i:s');
                $error['step'] = $request_data['step'];
                $error['msg'] = '七牛接口报错';
                $error['response'] = $request_data;
                SystemServer::filePutContents(
                    $log_filename,
                    json_encode($error).PHP_EOL
                );
                $photographerWorkSource->status = 500;
                $photographerWorkSource->save();

                return;
            }
        } catch (\Exception $e) {
            $error = [];
            $error['log_time'] = date('i:s');
            $error['step'] = $request_data['step'];
            $error['msg'] = $e->getMessage();
            SystemServer::filePutContents(
                $log_filename,
                json_encode($error).PHP_EOL
            );
            $photographerWorkSource->status = 500;
            $photographerWorkSource->save();

            return;
        }
    }
}
