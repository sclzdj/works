<?php

namespace App\Http\Controllers\Api\Notify;

use App\Http\Controllers\Api\BaseController;
use App\Model\Index\AsyncBaiduWorkSourceUpload;
use App\Model\Index\Photographer;
use App\Model\Index\PhotographerWork;
use App\Model\Index\PhotographerWorkSource;
use App\Model\Index\User;
use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
use function AlibabaCloud\Client\json;
use function Qiniu\base64_urlSafeEncode;

/**
 * 七牛相关控制器
 * Class QiniuController
 * @package App\Http\Controllers\Api\Notify
 */
class QiniuController extends BaseController
{
    /**
     * 七牛异步第三方抓取通知
     */
    public function fetch()
    {
        $request_data = \Request::all();
        $asyncBaiduWorkSourceUpload = AsyncBaiduWorkSourceUpload::where(
            ['id' => $request_data['async_baidu_work_source_upload_id']]
        )->first();
        if (!$asyncBaiduWorkSourceUpload) {
            return ErrLogServer::QiniuNotifyFetch('AsyncBaiduWorkSourceUpload记录不存在', $request_data);
        }
        $photographerWorkSource = PhotographerWorkSource::where(
            ['id' => $asyncBaiduWorkSourceUpload->photographer_work_source_id]
        )->first();
        if (!$photographerWorkSource) {
            return ErrLogServer::QiniuNotifyFetch(
                '项目资源不存在',
                $request_data,
                $asyncBaiduWorkSourceUpload
            );
        }
        try {
            $request_data['width'] = $request_data['width'] ?? 0;
            $request_data['height'] = $request_data['height'] ?? 0;
            $bucket = 'zuopin';
            $buckets = config('custom.qiniu.buckets');
            $domain = $buckets[$bucket]['domain'] ?? '';
            $photographerWorkSource->key = $request_data['key'];
            $photographerWorkSource->url = $domain . '/' . $request_data['key'];
            $photographerWorkSource->size = $request_data['size'];
            $photographerWorkSource->width = $request_data['width'];
            $photographerWorkSource->height = $request_data['height'];
//            $photographerWorkSource->deal_key = $request_data['key'];
//            $photographerWorkSource->deal_url = $domain . '/' . $request_data['key'];
//            $photographerWorkSource->deal_size = $request_data['size'];
//            $photographerWorkSource->deal_width = $request_data['width'];
//            $photographerWorkSource->deal_height = $request_data['height'];
//            $photographerWorkSource->rich_key = $request_data['key'];
//            $photographerWorkSource->rich_url = $domain . '/' . $request_data['key'];
//            $photographerWorkSource->rich_size = $request_data['size'];
//            $photographerWorkSource->rich_width = $request_data['width'];
//            $photographerWorkSource->rich_height = $request_data['height'];
            $photographerWorkSource->save();
            $asyncBaiduWorkSourceUpload->status = 200;
            $asyncBaiduWorkSourceUpload->save();
        } catch (\Exception $e) {
            return ErrLogServer::QiniuNotifyFetch(
                (string)$e->getMessage(),
                $request_data,
                $asyncBaiduWorkSourceUpload,
                $photographerWorkSource
            );
        }
        if ($request_data['is_register_photographer'] == 0) {
            if ($photographerWorkSource->type == 'image') {
                $fops = ["imageMogr2/auto-orient/thumbnail/1200x|imageMogr2/auto-orient/colorspace/srgb|imageslim"];
                $qrst = SystemServer::qiniuPfop(
                    $bucket,
                    $request_data['key'],
                    $fops,
                    null,
                    config(
                        'app.url'
                    ) . '/api/notify/qiniu/fop?photographer_work_source_id=' . $photographerWorkSource->id . '&step=1',
                    true
                );
                if ($qrst['err']) {
                    return ErrLogServer::QiniuNotifyFop(
                        0,
                        '持久化请求失败',
                        $request_data,
                        $photographerWorkSource,
                        $qrst['err']
                    );
                }
            }
        }
    }

    /**
     * 七牛持久化异步通知
     */
    public function fop()
    {
        $request_data = \Request::all();
        \Log::error(var_export($request_data, 1));
        // 判断项目资源是否存在
        $photographerWorkSource = PhotographerWorkSource::where(
            ['id' => $request_data['photographer_work_source_id']]
        )->first();
        if (!$photographerWorkSource) {
            return ErrLogServer::QiniuNotifyFop($request_data['step'], '项目资源不存在：', $request_data);
        }
        // 判断项目是否存在
        $photographerWork = PhotographerWork::where(['id' => $photographerWorkSource->photographer_work_id])->first();
        if (!$photographerWork) {
            return ErrLogServer::QiniuNotifyFop(
                $request_data['step'],
                '项目不存在',
                $request_data,
                $photographerWorkSource
            );
        }
        // 判断用户是否存在
        $photographer = Photographer::where(['id' => $photographerWork->photographer_id])->first();
        if (!$photographer) {
            return ErrLogServer::QiniuNotifyFop(
                $request_data['step'],
                '用户不存在',
                $request_data,
                $photographerWorkSource
            );
        }
        // 判断作者是否存在
        $user = User::where(['photographer_id' => $photographerWork->photographer_id])->first();
        if (!$user) {
            return ErrLogServer::QiniuNotifyFop($request_data['step'], '用户不存在', $request_data, $photographerWorkSource);
        }
        // 设置七牛信息
        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';

        try {
            // 判断如果code 不等于0报错
            if ($request_data['code'] != 0) {
                return ErrLogServer::QiniuNotifyFop(
                    $request_data['step'],
                    '七牛持久化接口通知报错',
                    $request_data,
                    $photographerWorkSource
                );
            }
            // 判断项目第0个不存在报错，
            if (!isset($request_data['items'][0]) ||
                (isset($request_data['items'][0]) && $request_data['items'][0]['code'] != 0)
            ) {
                return ErrLogServer::QiniuNotifyFop(
                    $request_data['step'],
                    '七牛持久化接口通知第一条持久化报错或返回信息不存在',
                    $request_data,
                    $photographerWorkSource
                );
            }

            if ($request_data['step'] == 1) {
                $photographerWorkSource->deal_key = $request_data['items'][0]['key'];
                $photographerWorkSource->deal_url = $domain . '/' . $request_data['items'][0]['key'];
                $photographerWorkSource->rich_key = $request_data['items'][0]['key'];
                $photographerWorkSource->rich_url = $domain . '/' . $request_data['items'][0]['key'];
                $photographerWorkSource->save();
                if ($photographerWorkSource->type == 'image') {
                    $response = SystemServer::request('GET', $photographerWorkSource->deal_url . '?imageInfo');
                    if ($response['code'] == 200) {
                        if (isset($response['data']['code']) && $response['data']['code'] != 200) {
                            return ErrLogServer::QiniuNotifyFop(
                                $request_data['step'],
                                '七牛请求图片信息接口失败',
                                $request_data,
                                $photographerWorkSource,
                                $response['data']
                            );
                        } else {
                            $photographerWorkSource->deal_size = $response['data']['size'];
                            $photographerWorkSource->deal_width = $response['data']['width'];
                            $photographerWorkSource->deal_height = $response['data']['height'];
                            $photographerWorkSource->rich_size = $response['data']['size'];
                            $photographerWorkSource->rich_width = $response['data']['width'];
                            $photographerWorkSource->rich_height = $response['data']['height'];
                            $photographerWorkSource->save();

                            PhotographerWork::generateOneWaterMark($photographerWorkSource, $photographerWork, $photographer);
                        }
                    } else {
                        return ErrLogServer::QiniuNotifyFop(
                            $request_data['step'],
                            '系统请求七牛图片信息接口时失败：' . $response['msg'],
                            $request_data,
                            $photographerWorkSource,
                            $response
                        );
                    }
                } elseif ($photographerWorkSource->type == 'video') {

                }
            }  // 先处理处理1200的结果，在发送生成水印图
            elseif ($request_data['step'] == 2) { // 这个是接受水印的处理
                switch ($photographerWorkSource->type) {
                    case "image":
                        $sort = $request_data['sort'] ?? 0;
                        $key = $request_data['items'][$sort]['key'] ?? $request_data['items'][0]['key'];
                        $photographerWorkSource->rich_key = $key;
                        $photographerWorkSource->rich_url = $domain . '/' . $key;
                        $photographerWorkSource->save();
                        $response = SystemServer::request('GET', $photographerWorkSource->rich_url . '?imageInfo');
                        if ($response['code'] != 200) {
                            return ErrLogServer::QiniuNotifyFop(
                                $request_data['step'],
                                '系统请求七牛图片信息接口时失败：' . $response['msg'],
                                $request_data,
                                $photographerWorkSource,
                                $response
                            );
                        }

                        if (isset($response['data']['code']) && $response['data']['code'] != 200) {
                            return ErrLogServer::QiniuNotifyFop(
                                $request_data['step'],
                                '七牛请求图片信息接口失败',
                                $request_data,
                                $photographerWorkSource,
                                $response['data']
                            );
                        }

                        $photographerWorkSource->rich_size = $response['data']['size'];
                        $photographerWorkSource->rich_width = $response['data']['width'];
                        $photographerWorkSource->rich_height = $response['data']['height'];
                        $photographerWorkSource->save();

                        break;
                    case "video":
                        break;
                    default:
                }

            } elseif ($request_data['step'] == 3) {
            }
//            elseif ($request_data['step'] == 4) {  // 项目分享图
//                $photographerWork->share_url = $request_data['items'][0]['key'];
//                $photographerWork->save();
//            } elseif ($request_data['step'] == 5) {  // 个人分享图
//                $photographer->share_url = $request_data['items'][0]['key'];
//                $photographer->save();
//            }

        } catch (\Exception $e) {
            return ErrLogServer::QiniuNotifyFop(
                $request_data['step'],
                $e->getMessage(),
                $request_data,
                $photographerWorkSource
            );
        }
    }
}
