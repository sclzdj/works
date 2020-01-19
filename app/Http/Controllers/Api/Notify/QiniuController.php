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
                '作品集资源不存在',
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
            $photographerWorkSource->deal_key = $request_data['key'];
            $photographerWorkSource->deal_url = $domain . '/' . $request_data['key'];
            $photographerWorkSource->deal_size = $request_data['size'];
            $photographerWorkSource->deal_width = $request_data['width'];
            $photographerWorkSource->deal_height = $request_data['height'];
            $photographerWorkSource->rich_key = $request_data['key'];
            $photographerWorkSource->rich_url = $domain . '/' . $request_data['key'];
            $photographerWorkSource->rich_size = $request_data['size'];
            $photographerWorkSource->rich_width = $request_data['width'];
            $photographerWorkSource->rich_height = $request_data['height'];
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
                $fops = ["imageMogr2/thumbnail/1200x|imageMogr2/colorspace/srgb|imageslim"];
                $qrst = SystemServer::qiniuPfop(
                    $bucket,
                    $request_data['key'],
                    $fops,
                    null,
                    config(
                        'app.url'
                    ) . '/api/notify/qiniu/fop?photographer_work_source_id=' . $photographerWorkSource->id . '&step=3',
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
        \Log::warning(var_export($request_data, 1));
        $photographerWorkSource = PhotographerWorkSource::where(
            ['id' => $request_data['photographer_work_source_id']]
        )->first();
        if (!$photographerWorkSource) {
            return ErrLogServer::QiniuNotifyFop($request_data['step'], '作品集资源不存在：', $request_data);
        }
        $photographerWork = PhotographerWork::where(['id' => $photographerWorkSource->photographer_work_id])->first();
        if (!$photographerWork) {
            return ErrLogServer::QiniuNotifyFop(
                $request_data['step'],
                '作品集不存在',
                $request_data,
                $photographerWorkSource
            );
        }
        $photographer = Photographer::where(['id' => $photographerWork->photographer_id])->first();
        if (!$photographer) {
            return ErrLogServer::QiniuNotifyFop(
                $request_data['step'],
                '摄影师不存在',
                $request_data,
                $photographerWorkSource
            );
        }
        $user = User::where(['photographer_id' => $photographerWork->photographer_id])->first();
        if (!$user) {
            return ErrLogServer::QiniuNotifyFop($request_data['step'], '用户不存在', $request_data, $photographerWorkSource);
        }
        try {
            if ($request_data['code'] == 0) {
                if (isset($request_data['items'][0]) && $request_data['items'][0]['code'] == 0) {
                    $bucket = 'zuopin';
                    $buckets = config('custom.qiniu.buckets');
                    $domain = $buckets[$bucket]['domain'] ?? '';
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

                                    $water1_image = \Qiniu\base64_urlSafeEncode($photographerWorkSource->deal_url);
                                    $xacode = User::createXacode2($photographerWork->id, 'photographer_work');
                                    if ($xacode) {
                                        $water2_image = \Qiniu\base64_urlSafeEncode(
                                            $xacode . '|imageMogr2/thumbnail/185x185!'
                                        );
                                    }
                                    else {
                                        $water2_image = \Qiniu\base64_urlSafeEncode(
                                            $domain . '/' . config(
                                                'custom.qiniu.crop_work_source_image_bg'
                                            ) . '?imageMogr2/thumbnail/210x210!|roundPic/radius/!50p'
                                        );
                                    }
//                                    $water2_image_dy = $response['data']['height'] - 105;
//                                    $water3_text = \Qiniu\base64_urlSafeEncode("我是摄影师" . $photographer->name);
//                                    $water3_text_dy = $response['data']['height'] + 130;
//                                    $water4_text = \Qiniu\base64_urlSafeEncode("微信扫一扫，看我的全部作品");
//                                    $water4_text_dy = $response['data']['height'] + 160;
//                                    $fops = ["imageMogr2/auto-orient/crop/{$response['data']['width']}x" . ($response['data']['height'] + 250) . "|watermark/3/image/{$water1_image}/gravity/North/dx/0/dy/0/image/{$water2_image}/gravity/North/dx/0/dy/{$water2_image_dy}/text/{$water3_text}/fontsize/500/gravity/North/dx/0/dy/{$water3_text_dy}/text/{$water4_text}/fontsize/500/gravity/North/dx/0/dy/{$water4_text_dy}|imageslim"];
                                    $hanlde = [];
                                    $hanlde[] = "imageMogr2/auto-orient/crop/" . $response['data']['width'] . 'x' . ($response['data']['height'] + 250);
                                    $hanlde[] = "|watermark/3/image/{$water1_image}/gravity/North/dx/0/dy/0/";
                                    $hanlde[] = "|watermark/3/image/" . base64_encode("https://file.zuopin.cloud/Fgz6Zf0EmsLVLvpCf73jBDaCPr9T") . "/gravity/South/dx/0/dy/0/";
                                    $hanlde[] = "|watermark/3/image/{$water2_image}/gravity/SouthEast/dx/57/dy/47/";

//                                    \Log::debug(var_export($photographerWork->toArray() , 1));
                                    \Log::debug(var_export($photographerWork->customer_name , 1));
                                    $hanlde[] = "text/" . \Qiniu\base64_urlSafeEncode($photographerWork->customer_name) . "/fontsize/800/fill/" . base64_urlSafeEncode("#323232") . "/fontstyle/".base64_urlSafeEncode("Bold")."/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/71/dy/162/";
                                    $fistX = 75;
                                    // 根据字体来判断宽度 中文40 数字字母20
                                    for ($i = 0; $i < mb_strlen($photographerWork->customer_name); $i++) {
                                        $char = mb_substr($photographerWork->customer_name, $i, 1);
                                        if (ord($char) > 126) {
                                            $fistX += 42;
                                        } else {
                                            $fistX += 26;
                                        }
                                    }

                                    $hanlde[] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode("https://file.zuopin.cloud/FlwzUiAItXVuajVB1_WNoteI-Fiw") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/" . $fistX . "/dy/170/";
                                    $secondX = $fistX + 45;
                                    $hanlde[] = "text/" . \Qiniu\base64_urlSafeEncode($photographer->name) . "/fontsize/800/fill/" . base64_urlSafeEncode("#C8C8C8") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/" . $secondX . "/dy/162/";

                                    $count = PhotographerWorkSource::where('photographer_work_id', $photographerWorkSource->photographer_work_id)->count();
                                    $hanlde[] = "text/" . \Qiniu\base64_urlSafeEncode("微信扫一扫，看剩余" . ($count - 1) . "张作品") . "/fontsize/609/fill/" . base64_urlSafeEncode("#F7F7F7") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/100/dy/78/";
                                    $hanlde[] = "|imageslim";

                                    $fops[] = implode($hanlde);

                                    \Log::debug( implode($hanlde));
                                    $qrst = SystemServer::qiniuPfop(
                                        $bucket,
                                        config('custom.qiniu.crop_work_source_image_bg'),
                                        $fops,
                                        null,
                                        config(
                                            'app.url'
                                        ) . '/api/notify/qiniu/fop?photographer_work_source_id=' . $photographerWorkSource->id . '&step=2&width=' . $response['data']['width'] . '&height=' . $response['data']['height'],
                                        true
                                    );
                                    if ($qrst['err']) {
                                        return ErrLogServer::QiniuNotifyFop(
                                            $request_data['step'],
                                            '持久化请求失败',
                                            $request_data,
                                            $photographerWorkSource,
                                            $qrst['err']
                                        );
                                    }

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
                    } elseif ($request_data['step'] == 2) {
                        if ($photographerWorkSource->type == 'image') {
                            $sort = $request_data['sort'] ?? 0;
                            $key = $request_data['items'][$sort]['key'] ?? $request_data['items'][0]['key'];
                            $photographerWorkSource->rich_key = $key;
                            $photographerWorkSource->rich_url = $domain . '/' . $key;
                            $photographerWorkSource->save();
                            $response = SystemServer::request('GET', $photographerWorkSource->rich_url . '?imageInfo');
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
                                    $photographerWorkSource->rich_size = $response['data']['size'];
                                    $photographerWorkSource->rich_width = $response['data']['width'];
                                    $photographerWorkSource->rich_height = $response['data']['height'];
                                    $photographerWorkSource->save();
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
                    } elseif ($request_data['step'] == 3) {  // 其实就是原先1的功能，给编辑项目用

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

                                    // 生成水印图
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

                    } elseif ($request_data['step'] == 4) {  // 把持久化的图放到作品集
//                        \Log::debug(json_encode($request_data, JSON_UNESCAPED_UNICODE));
                        $photographerWork->share_url = $request_data['items'][0]['key'];
                        $photographerWork->save();
                    } elseif ($request_data['step'] == 5) {  // 把持久化的图放到作品集
//                        \Log::debug(json_encode($request_data, JSON_UNESCAPED_UNICODE));
                        $photographer->share_url = $request_data['items'][0]['key'];
                        $photographer->save();
                    }
                } else {
                    return ErrLogServer::QiniuNotifyFop(
                        $request_data['step'],
                        '七牛持久化接口通知第一条持久化报错或返回信息不存在',
                        $request_data,
                        $photographerWorkSource
                    );
                }
            } else {
                return ErrLogServer::QiniuNotifyFop(
                    $request_data['step'],
                    '七牛持久化接口通知报错',
                    $request_data,
                    $photographerWorkSource
                );
            }
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
