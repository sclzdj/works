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
            if (isset($request_data['code']) && $request_data['code'] != 0) {
                return ErrLogServer::QiniuNotifyFetch(
                    '七牛异步抓取通知结果报错：'.$request_data['code'].($request_data['err'] ?? ''),
                    $request_data,
                    $asyncBaiduWorkSourceUpload,
                    $photographerWorkSource
                );
            }
            $request_data['width'] = $request_data['width'] ?? 0;
            $request_data['height'] = $request_data['height'] ?? 0;
            $bucket = 'zuopin';
            $buckets = config('custom.qiniu.buckets');
            $domain = $buckets[$bucket]['domain'] ?? '';
            $photographerWorkSource->key = $request_data['key'];
            $photographerWorkSource->url = $domain.'/'.$request_data['key'];
            $photographerWorkSource->size = $request_data['size'];
            $photographerWorkSource->width = $request_data['width'];
            $photographerWorkSource->height = $request_data['height'];
            if ($photographerWorkSource->type != 'image') {
                $photographerWorkSource->deal_key = $request_data['key'];
                $photographerWorkSource->deal_url = $domain.'/'.$request_data['key'];
                $photographerWorkSource->deal_size = $request_data['size'];
                $photographerWorkSource->deal_width = $request_data['width'];
                $photographerWorkSource->deal_height = $request_data['height'];
                $photographerWorkSource->rich_key = $request_data['key'];
                $photographerWorkSource->rich_url = $domain.'/'.$request_data['key'];
                $photographerWorkSource->rich_size = $request_data['size'];
                $photographerWorkSource->rich_width = $request_data['width'];
                $photographerWorkSource->rich_height = $request_data['height'];
            }
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
        if ($photographerWorkSource->type == 'image') {
            $fops = ["imageMogr2/auto-orient/thumbnail/1200x|imageMogr2/auto-orient/colorspace/srgb|imageslim"];
            $qrst = SystemServer::qiniuPfop(
                $bucket,
                $request_data['key'],
                $fops,
                null,
                config(
                    'app.url'
                ).'/api/notify/qiniu/fopDeal?photographer_work_source_id='.$photographerWorkSource->id,
                true
            );
            if ($qrst['err']) {
                return ErrLogServer::QiniuNotifyFop(
                    '处理图片持久请求',
                    '持久化请求失败',
                    $request_data,
                    $photographerWorkSource,
                    $qrst['err']
                );
            }
        }
    }

    /**
     * 七牛持久化处理资源异步通知
     */
    public function fopDeal()
    {
        $step = '处理资源通知请求';
        $request_data = \Request::all();
        // 判断项目资源是否存在
        $photographerWorkSource = PhotographerWorkSource::where(
            ['id' => $request_data['photographer_work_source_id']]
        )->first();
        if (!$photographerWorkSource) {
            return ErrLogServer::QiniuNotifyFop(
                $step,
                '项目资源不存在：',
                $request_data
            );
        }
        switch ($photographerWorkSource->type) {
            case 'image':
                $step = '处理图片通知请求';
                break;
            case 'video':
                $step = '处理视频通知请求';
                break;
        }
        // 设置七牛信息
        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';
        try {
            // 判断如果code 不等于0报错
            if ($request_data['code'] != 0) {
                return ErrLogServer::QiniuNotifyFop(
                    $step,
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
                    $step,
                    '七牛持久化接口通知第一条持久化报错或返回信息不存在',
                    $request_data,
                    $photographerWorkSource
                );
            }
            $photographerWorkSource->deal_key = $request_data['items'][0]['key'];
            $photographerWorkSource->deal_url = $domain.'/'.$request_data['items'][0]['key'];
            $photographerWorkSource->rich_key = $request_data['items'][0]['key'];
            $photographerWorkSource->rich_url = $domain.'/'.$request_data['items'][0]['key'];
            $photographerWorkSource->save();
            if ($photographerWorkSource->type == 'image') {
                $response = SystemServer::request('GET', $photographerWorkSource->deal_url.'?imageInfo');
                if ($response['code'] == 200) {
                    if (isset($response['data']['error']) || (isset($response['data']['code']) && $response['data']['code'] != 200)) {
                        return ErrLogServer::QiniuNotifyFop(
                            '处理图片信息请求',
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
                        PhotographerWorkSource::dealNotifyRunGenerateWatermark($photographerWorkSource->id);
                    }
                } else {
                    return ErrLogServer::QiniuNotifyFop(
                        '处理图片信息请求',
                        '系统请求七牛图片信息接口时失败：'.$response['msg'],
                        $request_data,
                        $photographerWorkSource,
                        $response
                    );
                }
            } elseif ($photographerWorkSource->type == 'video') {

            }
        } catch (\Exception $e) {
            return ErrLogServer::QiniuNotifyFop(
                $step,
                $e->getMessage(),
                $request_data,
                $photographerWorkSource
            );
        }
    }

    /**
     * 七牛持久化水印资源异步通知
     * @return bool|int
     */
    public function fopRich()
    {
        $step = '水印资源通知请求';
        $request_data = \Request::all();
        // 判断项目资源是否存在
        $photographerWorkSource = PhotographerWorkSource::where(
            ['id' => $request_data['photographer_work_source_id']]
        )->first();
        if (!$photographerWorkSource) {
            return ErrLogServer::QiniuNotifyFop(
                $step,
                '项目资源不存在',
                $request_data
            );
        }
        switch ($photographerWorkSource->type) {
            case 'image':
                $step = '水印图片通知请求';
                break;
            case 'video':
                $step = '水印视频通知请求';
                break;
        }
        // 设置七牛信息
        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';
        try {
            // 判断如果code 不等于0报错
            if ($request_data['code'] != 0) {
                return ErrLogServer::QiniuNotifyFop(
                    $step,
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
                    $step,
                    '七牛持久化接口通知第一条持久化报错或返回信息不存在',
                    $request_data,
                    $photographerWorkSource
                );
            }
            switch ($photographerWorkSource->type) {
                case "image":
                    PhotographerWorkSource::richNotify(
                        $photographerWorkSource->id,
                        $request_data['job_id'],
                        $request_data
                    );
                    break;
                case "video":
                    break;
                default:
            }
        } catch (\Exception $e) {
            return ErrLogServer::QiniuNotifyFop(
                $step,
                $e->getMessage(),
                $request_data,
                $photographerWorkSource
            );
        }
    }
}
