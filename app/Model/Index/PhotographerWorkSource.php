<?php

namespace App\Model\Index;

use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
use Illuminate\Database\Eloquent\Model;
use function Qiniu\base64_urlSafeEncode;

class PhotographerWorkSource extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'photographer_work_id',
        'key',
        'url',
        'size',
        'width',
        'height',
        'image_ave',
        'exif',
        'deal_key',
        'deal_url',
        'deal_size',
        'deal_width',
        'deal_height',
        'rich_key',
        'rich_url',
        'rich_size',
        'rich_width',
        'rich_height',
        'is_newest_rich',
        'type',
        'origin',
        'status',
        'review',
        'sort',
        'is_new_source',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
//        'exif',
    ];


    /**
     * 允许查询的字段
     * @return array
     */
    public static function allowFields()
    {
        return [
            'id',
            'photographer_work_id',
            'key',
            'url',
            'size',
            'width',
            'height',
            'exif',
            'image_ave',
            'deal_key',
            'deal_url',
            'deal_size',
            'deal_width',
            'deal_height',
            'rich_key',
            'rich_url',
            'rich_size',
            'rich_width',
            'rich_height',
            'is_newest_rich',
            'type',
            'origin',
            'review',
            'created_at',
        ];
    }

    /**
     * 修改时原来的图片是否需要执行水印图持久化生成任务
     * @param array $new_work_params
     * @param array $old_work_params
     * @return bool
     */
    static function editIsRunGenerateWatermark($new_work_params = [], $old_work_params = [])
    {
        if (isset($new_work_params['customer_name']) && isset($old_work_params['customer_name']) && $new_work_params['customer_name'] !== $old_work_params['customer_name']) {
            return true;
        }
        if (isset($new_work_params['source_count']) && isset($old_work_params['source_count']) && $new_work_params['source_count'] != $old_work_params['source_count']) {
            return true;
        }

        return false;
    }

    /**
     * 修改时原来的图片执行水印图持久化生成任务
     * @param $photographer_work_source_id 资源id
     * @param $edit_node 修改节点
     */
    static function editRunGenerateWatermark($photographer_work_source_id, $edit_node)
    {
        $photographerWorkSource = PhotographerWorkSource::where('id', $photographer_work_source_id)->first();
        if (!$photographerWorkSource) {
            return ErrLogServer::qiniuNotifyFop(
                '水印图片持久请求',
                'PhotographerWorkSource不存在',
                "",
                $photographerWorkSource
            );
        }
        $at = date('Y-m-d H:i:s');
        $qiniuPfopRichSourceJob = QiniuPfopRichSourceJob::create(
            [
                'photographer_work_source_id' => $photographer_work_source_id,
                'edit_node' => $edit_node,
                'edit_at' => $at,
            ]
        );
        $qiniuPfopRichSourceJob_tmp = $qiniuPfopRichSourceJob;
        $insert = $qiniuPfopRichSourceJob_tmp->toArray();
        if ($photographerWorkSource->deal_key !== '') {
            $qiniuPfopRichSourceJob->status = 1;
            $insert['run_at'] = $at;
            self::generateWatermark($photographerWorkSource->id, $qiniuPfopRichSourceJob->id);
        }
        $qiniuPfopRichSourceJob->save();
        $photographerWorkSource->is_newest_rich = 0;
        $photographerWorkSource->save();
        QiniuPfopRichSourceJobLog::insert($insert);
    }

    /**
     * 1200图通知时原来的图片执行水印图持久化生成任务
     * @param $photographer_work_source_id
     */
    static public function dealNotifyRunGenerateWatermark($photographer_work_source_id)
    {
        $qiniuPfopRichSourceJob = QiniuPfopRichSourceJob::where(
            ['photographer_work_source_id' => $photographer_work_source_id]
        )->orderBy('created_at', 'desc')->orderBy('id', 'desc')->first();
        if ($qiniuPfopRichSourceJob && $qiniuPfopRichSourceJob->status == 0) {
            $qiniuPfopRichSourceJob->status = 1;
            $qiniuPfopRichSourceJob->save();
            $qiniuPfopRichSourceJobLog = QiniuPfopRichSourceJobLog::where(['id' => $qiniuPfopRichSourceJob->id])->first(
            );

            if ($qiniuPfopRichSourceJobLog){
                $qiniuPfopRichSourceJobLog->status = 1;
                $qiniuPfopRichSourceJobLog->run_at = date('Y-m-d H:i:s');
                $qiniuPfopRichSourceJobLog->save();
            }

            self::generateWatermark($photographer_work_source_id, $qiniuPfopRichSourceJob->id);
        }
    }

    /**
     * 七牛水印图持久化通知
     * @param $photographer_work_source_id
     * @param $qiniu_pfop_rich_source_job_id
     * @param $qiniu_response
     */
    static public function richNotify($photographer_work_source_id, $qiniu_pfop_rich_source_job_id, $qiniu_response)
    {
        $step = '水印图片通知请求';
        $photographerWorkSource = PhotographerWorkSource::where('id', $photographer_work_source_id)->first();
        if (!$photographerWorkSource) {
            return ErrLogServer::qiniuNotifyFop(
                $step,
                'PhotographerWorkSource不存在',
                "",
                $photographerWorkSource
            );
        }
        $qiniuPfopRichSourceJob = QiniuPfopRichSourceJob::where(
            ['photographer_work_source_id' => $photographer_work_source_id]
        )->orderBy('created_at', 'desc')->orderBy('id', 'desc')->first();
        $qiniuPfopRichSourceJobLog = QiniuPfopRichSourceJobLog::where('id', $qiniuPfopRichSourceJob->id)->first();
        $at = date('Y-m-d H:i:s');
        if ($qiniuPfopRichSourceJobLog) $qiniuPfopRichSourceJobLog->response_at = $at;
        if ($qiniuPfopRichSourceJobLog) $qiniuPfopRichSourceJobLog->qiniu_response = json_encode($qiniu_response, JSON_UNESCAPED_UNICODE);
        if ($qiniu_response['code'] != 0) {
            $qiniuPfopRichSourceJob->status = 500;
            if ($qiniuPfopRichSourceJobLog) $qiniuPfopRichSourceJobLog->status = 500;
            $qiniuPfopRichSourceJob->save();
            if ($qiniuPfopRichSourceJobLog) $qiniuPfopRichSourceJobLog->save();

            return ErrLogServer::qiniuNotifyFop(
                $step,
                '七牛持久化接口通知报错',
                $qiniu_response,
                $photographerWorkSource
            );
        }
        // 判断项目第0个不存在报错，
        if (!isset($qiniu_response['items'][0]) ||
            (isset($qiniu_response['items'][0]) && $qiniu_response['items'][0]['code'] != 0)
        ) {
            $qiniuPfopRichSourceJob->status = 500;
            if ($qiniuPfopRichSourceJobLog) $qiniuPfopRichSourceJobLog->status = 500;
            $qiniuPfopRichSourceJob->save();
            if ($qiniuPfopRichSourceJobLog) $qiniuPfopRichSourceJobLog->save();

            return ErrLogServer::qiniuNotifyFop(
                $step,
                '七牛持久化接口通知第一条持久化报错或返回信息不存在',
                $qiniu_response,
                $photographerWorkSource
            );
        }
        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';
        $qiniuPfopRichSourceJob->status = 200;
        if ($qiniuPfopRichSourceJobLog)  $qiniuPfopRichSourceJobLog->status = 200;
        if ($qiniuPfopRichSourceJobLog)  $qiniuPfopRichSourceJobLog->rich_key = $qiniu_response['items'][0]['key'];
        if ($qiniuPfopRichSourceJobLog)  $qiniuPfopRichSourceJobLog->rich_url = $domain.'/'.$qiniu_response['items'][0]['key'];
        $qiniuPfopRichSourceJob->save();
        if ($qiniuPfopRichSourceJobLog) $qiniuPfopRichSourceJobLog->save();
        QiniuPfopRichSourceJob::where('photographer_work_source_id', $photographer_work_source_id)->where(
            'id',
            '<',
            $qiniuPfopRichSourceJob->id
        )->whereIn('status', [200, 500])->delete();
        if ($qiniuPfopRichSourceJob->id == $qiniu_pfop_rich_source_job_id) {
            $photographerWorkSource->rich_key = $qiniu_response['items'][0]['key'];
            $photographerWorkSource->rich_url = $domain.'/'.$qiniu_response['items'][0]['key'];
            $photographerWorkSource->is_newest_rich = 1;
            $response = SystemServer::request('GET', $photographerWorkSource->rich_url.'?imageInfo');
            if ($response['code'] != 200) {
                $photographerWorkSource->status = 500;
                $photographerWorkSource->save();

                return ErrLogServer::qiniuNotifyFop(
                    '水印图片信息请求',
                    '系统请求七牛图片信息接口时失败',
                    $response,
                    $photographerWorkSource
                );
            }
            if (isset($response['data']['error']) || (isset($response['data']['code']) && $response['data']['code'] != 0)) {
                $photographerWorkSource->status = 500;
                $photographerWorkSource->save();

                return ErrLogServer::qiniuNotifyFop(
                    '水印图片信息请求',
                    '七牛请求图片信息接口失败',
                    $response['data'],
                    $photographerWorkSource
                );
            }
            if (!isset($response['data']['size'])) {
                SystemServer::filePutContents('logs/cesi/'.date('YmdHis').mt_rand(1000,9999).'.log', json_encode($response));
            }
            $photographerWorkSource->rich_size = $response['data']['size'];
            $photographerWorkSource->rich_width = $response['data']['width'];
            $photographerWorkSource->rich_height = $response['data']['height'];
            $photographerWorkSource->save();
            if ($photographerWorkSource->image_ave === '') {
                /*平均色调*/
                $res_ave = SystemServer::request('GET', $photographerWorkSource->url.'?imageAve');
                if ($res_ave['code'] == 200) {
                    if (!isset($res_ave['data']['error']) || (isset($res_ave['data']['code']) && $res_ave['data']['code'] == 200)) {
                        if (isset($res_ave['data']['RGB'])) {
                            $photographerWorkSource->image_ave = $res_ave['data']['RGB'];
                            $photographerWorkSource->save();
                        }
                    }
                }
                /*平均色调 END*/
            }
        }
    }

    /*
     * 生成水印图
     * @param string $photographer_work_source_id 作品图资源id
     * @param string $qiniu_pfop_rich_source_job_id 七牛持久化水印任务id
     * @return void
     */
    static public function generateWatermark($photographer_work_source_id, $qiniu_pfop_rich_source_job_id)
    {
        $step = '水印图片持久请求';
        $photographerWorkSource = PhotographerWorkSource::where('id', $photographer_work_source_id)->first();
        if (!$photographerWorkSource) {
            return ErrLogServer::qiniuNotifyFop(
                $step,
                'PhotographerWorkSource不存在'
            );
        }
        $photographerWork = PhotographerWork::where(['id' => $photographerWorkSource->photographer_work_id])->first();
        if (!$photographerWork) {
            return ErrLogServer::qiniuNotifyFop(
                $step,
                'photographerWork不存在',
                [],
                $photographerWorkSource
            );
        }
        $photographer = Photographer::where(['id' => $photographerWork->photographer_id])->first();
        if (!$photographer) {
            return ErrLogServer::qiniuNotifyFop(
                $step,
                'photographer不存在',
                [],
                $photographerWorkSource
            );
        }

        //     if (empty($photographerWorkSource->deal_key)) {
        $srcKey = config('custom.qiniu.crop_work_source_image_bg');
//        } else {
//            $srcKey = $photographerWorkSource->deal_key;
//        }

        $srcKey = config('custom.qiniu.crop_work_source_image_bg');

        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';
        // 生成水印图
        $xacode = PhotographerWork::getXacode($photographerWork->id);
        if ($xacode) {
            $water2_image = \Qiniu\base64_urlSafeEncode(
                $xacode.'|imageMogr2/auto-orient/thumbnail/185x185!'
            );
        } else {
            $water2_image = \Qiniu\base64_urlSafeEncode(
                $domain.'/'.config(
                    'custom.qiniu.crop_work_source_image_bg'
                ).'?imageMogr2/auto-orient/thumbnail/210x210!|roundPic/radius/!50p'
            );
        }

        // 计算出作品名的初始位置
        $fistX = self::calcWaterText($photographerWork->name);
        // 水印剩余图片的数量和文字
        $count = PhotographerWorkSource::where(
            'photographer_work_id',
            $photographerWorkSource->photographer_work_id
        )->where('status', 200)->count();
        $text = $count - 1 <= 0 ? '微信扫一扫，看我的全部作品' : "微信扫一扫，看剩余".($count - 1)."张作品";

        $hanlde = [];
        // 对原图进行加高处理 增加水印框架图位置
        $hanlde[] = "imageMogr2/auto-orient/thumbnail/1200x".($photographerWorkSource->deal_height + 230).'!';
        // 作品图
        if ($photographerWorkSource->deal_url) {
            $hanlde[] = "|watermark/3/image/".\Qiniu\base64_urlSafeEncode(
                    $photographerWorkSource->deal_url
                )."/gravity/North/dx/0/dy/0/";
        }
        // 水印底部框架图
        $hanlde[] = "|watermark/3/image/".base64_encode(
                "https://file.zuopin.cloud/Fte_WqPqt7fBcyIsr2Lf_69VVhzK"
            )."/gravity/South/dx/0/dy/0/";
        // 水印小程序
        $hanlde[] = "|watermark/3/image/{$water2_image}/gravity/SouthEast/dx/57/dy/47/";
        // 水印作品名
        $hanlde[] = "text/".\Qiniu\base64_urlSafeEncode(
                $photographerWork->name
            )."/fontsize/800/fill/".base64_urlSafeEncode("#323232")."/fontstyle/".base64_urlSafeEncode(
                "Bold"
            )."/font/".base64_urlSafeEncode("Microsoft YaHei")."/gravity/SouthWest/dx/71/dy/162/";
        // 水印中的 @
        $hanlde[] = "|watermark/3/image/".\Qiniu\base64_urlSafeEncode(
                "https://file.zuopin.cloud/FlwzUiAItXVuajVB1_WNoteI-Fiw"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/".$fistX."/dy/170/";
        // 水印的用户名字
//        $hanlde[] = "text/".\Qiniu\base64_urlSafeEncode($photographer->name)."/fontsize/800/fill/".base64_urlSafeEncode(
//                "#C8C8C8"
//            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/".($fistX + 45)."/dy/162/";
        $hanlde[] = "text/".\Qiniu\base64_urlSafeEncode($photographer->name)."/fontsize/800/fill/".base64_urlSafeEncode(
                "#C8C8C8"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/".($fistX + 45)."/dy/162/";
        // 水印最后一行 微信扫一扫
        $hanlde[] = "text/".\Qiniu\base64_urlSafeEncode($text)."/fontsize/609/fill/".base64_urlSafeEncode(
                "#F7F7F7"
            )."/font/".base64_urlSafeEncode("微软雅黑")."/gravity/SouthWest/dx/100/dy/78/";
        $hanlde[] = "|imageslim";

        $fops[] = implode($hanlde);

        $qrst = SystemServer::qiniuPfop(
            $bucket,
            $srcKey,
            $fops,
            null,
            config(
                'app.url'
            ).'/api/notify/qiniu/fopRich?photographer_work_source_id='.$photographerWorkSource->id.'&job_id='.$qiniu_pfop_rich_source_job_id,
            true
        );
        if ($qrst['err']) {
            return ErrLogServer::qiniuNotifyFop(
                $step,
                '持久化请求失败',
                [],
                $photographerWorkSource,
                $qrst['err']
            );
        }
    }

    static public function calcWaterText($customer_name)
    {
        $fistX = 75;
        for ($i = 0; $i < mb_strlen($customer_name); $i++) {
            $char = mb_substr($customer_name, $i, 1);
            if (ord($char) > 126) {
                $fistX += 42;
            } else {
                $fistX += 26;
            }
        }

        return $fistX;
    }
}
