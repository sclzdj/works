<?php

namespace App\Model\Index;

use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use function Qiniu\base64_urlSafeEncode;

class PhotographerWork extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'photographer_id',
        'customer_name',
        'photographer_work_customer_industry_id',
        'project_amount',
        'hide_project_amount',
        'sheets_number',
        'hide_sheets_number',
        'shooting_duration',
        'hide_shooting_duration',
        'photographer_work_category_id',
        'roof',
        'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * 允许查询的字段
     * @return array
     */
    public static function allowFields()
    {
        return [
            'id',
            'photographer_id',
            'customer_name',
            'photographer_work_customer_industry_id',
            'project_amount',
            'hide_project_amount',
            'sheets_number',
            'hide_sheets_number',
            'shooting_duration',
            'hide_shooting_duration',
            'photographer_work_category_id',
            'roof',
            'created_at',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function photographerWorkSources()
    {
        return $this->hasMany(PhotographerWorkSource::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function photographerWorkTags()
    {
        return $this->hasMany(PhotographerWorkTag::class);
    }

    /**
     * 作品集海报
     * @param $photographer_work_id
     * @return array
     */
    public static function poster($photographer_work_id)
    {
        $response = [];
        $photographer_work = PhotographerWork::where(
            ['status' => 200, 'id' => $photographer_work_id]
        )->first();
        if (!$photographer_work) {
            $response['code'] = 500;
            $response['msg'] = '摄影师作品集不存在';
            return $response;
        }
        $photographer = User::photographer($photographer_work->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            $response['code'] = 500;
            $response['msg'] = '摄影师不存在';
            return $response;
        }
        $user = User::where(['photographer_id' => $photographer->id])->first();
        if (!$user) {
            $response['code'] = 500;
            $response['msg'] = '用户不存在';
            return $response;
        }
        if ($user->identity != 1) {
            $response['code'] = 500;
            $response['msg'] = '用户不是摄影师';
            return $response;
        }
        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';
        $url = $domain . '/' . config('custom.qiniu.crop_work_source_image_bg');
        $deals = [];
        $deals[] = 'imageMogr2/crop/1200x2133';
        $photographer_work_source = $photographer_work->photographerWorkSources()->where(
            ['status' => 200, 'type' => 'image']
        )->orderBy(
            'sort',
            'asc'
        )->first();
        if ($photographer_work_source) {
            if ($photographer_work_source->deal_url) {
                $bg_img = $photographer_work_source->deal_url . '?imageMogr2/auto-orient/thumbnail/1200x/gravity/Center/crop/!1200x800-0-0|imageslim';
            } elseif ($photographer->bg_img) {
                $bg_img = $photographer->bg_img . '?imageMogr2/auto-orient/thumbnail/1200x/gravity/Center/crop/!1200x800-0-0|imageslim';
            } else {
                $bg_img = config('app.url') . '/' . 'images/poster_bg.jpg';
            }
        } else {
            if ($photographer->bg_img) {
                $bg_img = $photographer->bg_img . '?imageMogr2/auto-orient/thumbnail/1200x/gravity/Center/crop/!1200x800-0-0|imageslim';
            } else {
                $bg_img = config('app.url') . '/' . 'images/poster_bg.jpg';
            }
        }
        $xacode = User::createXacode($photographer_work_id, 'photographer_work');
        if ($xacode) {
            $xacode = $xacode . '|imageMogr2/thumbnail/250x250!';
        } else {
            $xacode = $domain . '/' . config('custom.qiniu.crop_work_source_image_bg') . '?imageMogr2/crop/250x250';
        }
        $watermark = 'watermark/3/image/' . \Qiniu\base64_urlSafeEncode($bg_img) . '/gravity/North/dx/0/dy/0';
        $watermark .= '/text/' . \Qiniu\base64_urlSafeEncode(
                '我是摄影师' . $photographer->name
            ) . '/fontsize/1500/fill/' . \Qiniu\base64_urlSafeEncode('#313131') . '/gravity/North/dx/0/dy/950';
        $watermark .= '/text/' . \Qiniu\base64_urlSafeEncode(
                '我为' . $photographer_work->customer_name
            ) . '/fontsize/1500/fill/' . \Qiniu\base64_urlSafeEncode('#313131') . '/gravity/North/dx/0/dy/1100';
        $watermark .= '/text/' . \Qiniu\base64_urlSafeEncode(
                '拍了一组作品'
            ) . '/fontsize/1500/fill/' . \Qiniu\base64_urlSafeEncode('#313131') . '/gravity/North/dx/0/dy/1250';
        $watermark .= '/text/' . \Qiniu\base64_urlSafeEncode(
                $photographer_work->project_amount . '元·' . $photographer_work->sheets_number . '张·' . $photographer_work->shooting_duration . '小时'
            ) . '/fontsize/800/fill/' . \Qiniu\base64_urlSafeEncode('#999999') . '/gravity/North/dx/0/dy/1420';
        $watermark .= '/image/' . \Qiniu\base64_urlSafeEncode($xacode) . '/gravity/North/dx/0/dy/1630';
        $watermark .= '/text/' . \Qiniu\base64_urlSafeEncode(
                '微信扫一扫 看完整作品'
            ) . '/fontsize/700/fill/' . \Qiniu\base64_urlSafeEncode('#4E4E4E') . '/gravity/North/dx/0/dy/1950';
        $deals[] = $watermark;
        $url .= '?' . implode('|', $deals);
        $response['code'] = 200;
        $response['msg'] = 'ok';
        $response['url'] = $url;

        return $response;
    }

    /**
     * 根据作品集id 生成作品分享图
     * @param $photographer_work_id
     * @return array
     */
    public static function generateShare($photographer_work_id)
    {
        $work = PhotographerWork::find($photographer_work_id);
        if (empty($work)) {
            return ['result' => false, 'msg' => "作品集不存在"];
        }

        $sheets_number = $work->hide_sheets_number == 1 ? '保密' : $work->sheets_number . '张';
        $project_number = $work->hide_project_amount == 1 ? '保密' : $work->project_amount . '元';
        $shooting_duration = $work->hide_shooting_duration == 1 ? '保密' : $work->shooting_duration . '小时';
        $customer_name = $work->customer_name;
        $buttonText = $project_number . '·' . $sheets_number . '·' . $shooting_duration;

        $firstPhoto = PhotographerWorkSource::where(
            [
                'photographer_work_id' => $work->id,
                'status' => 200,
            ]
        )->orderBy('created_at', 'asc')->first();

        if (empty($firstPhoto)) {
            return ['result' => false, 'msg' => "资源不存在"];
        }

        // 拿到七牛url
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets['zuopin']['domain'] ?? '';
        // 背景图
        $whiteBg = $domain . '/FtSr3gPOeI8CjSgh5fBkeHaIsJnm?imageMogr2/auto-orient/thumbnail/1200x960!';
        // 上面图
        $sharePhoto = $firstPhoto->deal_url . "?imageMogr2/auto-orient/crop/1200x657";

        $handleUrl = array();
        $handleUrl[0] = $whiteBg;
        $handleUrl[1] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode($sharePhoto) . "/gravity/North/dx/0/dy/0";
        $handleUrl[2] = "/text/" . \Qiniu\base64_urlSafeEncode($customer_name) . "/fontsize/1500/fill/" . base64_urlSafeEncode("#323232") . "/gravity/North/dx/0/dy/743";
        $handleUrl[3] = "/text/" . \Qiniu\base64_urlSafeEncode($buttonText) . "/fontsize/900/fill/" . base64_urlSafeEncode("#969696") . "/gravity/North/dx/0/dy/887";

        array_shift($handleUrl);

        $fops = ["imageMogr2/auto-orient/thumbnail/1200x960!" . implode("", $handleUrl)];
        $bucket = 'zuopin';
        $qrst = SystemServer::qiniuPfop(
            $bucket,
            "FtSr3gPOeI8CjSgh5fBkeHaIsJnm",
            $fops,
            null,
            config(
                'app.url'
            ) . '/api/notify/qiniu/fop?photographer_work_source_id=' . $firstPhoto->id . '&step=4',
            true
        );
        if ($qrst['err']) {
            ErrLogServer::QiniuNotifyFop(
                0,
                '七牛持久化接口返回错误信息',
                [],
                $firstPhoto,
                $qrst['err']
            );
        }

        return ['result' => true, 'msg' => "成功"];
    }
}
