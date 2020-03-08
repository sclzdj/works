<?php

namespace App\Model\Index;

use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        $deals[] = 'imageMogr2/auto-orient/crop/1200x2133';
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
        $scene = "1/{$photographer_work_id}";
        $xacode = User::createXacode($photographer->id, 'other', $scene);
        if ($xacode) {
            $xacode = $xacode . '|imageMogr2/auto-orient/thumbnail/250x250!';
        } else {
            $xacode = $domain . '/' . config('custom.qiniu.crop_work_source_image_bg') . '?imageMogr2/auto-orient/crop/250x250';
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
        $sharePhoto = $firstPhoto->deal_url . "?imageMogr2/auto-orient/gravity/Center/crop/1200x657|roundPic/radius/20";

        $handleUrl = array();
        $handleUrl[0] = $whiteBg;
        $handleUrl[1] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode($sharePhoto) . "/gravity/North/dx/0/dy/0";
        $handleUrl[2] = "/text/" . \Qiniu\base64_urlSafeEncode($customer_name) . "/fontsize/1700/fill/" . base64_urlSafeEncode("#323232") . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/South/dx/0/dy/136";
        $handleUrl[3] = "/text/" . \Qiniu\base64_urlSafeEncode($buttonText) . "/fontsize/1140/fill/" . base64_urlSafeEncode("#969696") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/South/dx/0/dy/20";

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

    public static function generateDealImage($photographer_work_id)
    {
        $photographerWork = PhotographerWork::find($photographer_work_id);
        if (empty($photographerWork)) {
            throw new \LogicException("作品集不存在");
        }

        $photographer = Photographer::where(['id' => $photographerWork->photographer_id])->first();
        if (!$photographer) {
            throw new \LogicException("摄影师不存在");
        }

        $photographerWorkSources = PhotographerWorkSource::where([
            'photographer_work_id' => $photographer_work_id,
            'status' => 200,
        ])->get();

        foreach ($photographerWorkSources as $photographerWorkSource) {
            $fops = ["imageMogr2/auto-orient/thumbnail/1200x|imageMogr2/auto-orient/colorspace/srgb|imageslim"];
            $bucket = 'zuopin';
            $qrst = SystemServer::qiniuPfop(
                $bucket,
                $photographerWorkSource->key,
                $fops,
                null,
                config(
                    'app.url'
                ).'/api/notify/qiniu/fop?photographer_work_source_id='.$photographerWorkSource->id.'&step=1',
                true
            );
            if ($qrst['err']) {
                ErrLogServer::QiniuNotifyFop(
                    0,
                    '七牛持久化接口返回错误信息',
                    "",
                    $photographerWorkSource,
                    $qrst['err']
                );
            }
        }
    }

    // 为作品集生成水印图
    public static function generateWatermark($photographer_work_id)
    {
        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';
        $photographerWork = PhotographerWork::find($photographer_work_id);
        if (empty($photographerWork)) {
            return ['result' => false, 'msg' => "作品集不存在"];
        }

        $photographer = Photographer::where(['id' => $photographerWork->photographer_id])->first();
        if (!$photographer) {
            return ['result' => false, 'msg' => "摄影师不存在"];
        }

        $photographerWorkSources = PhotographerWorkSource::where([
            'photographer_work_id' => $photographer_work_id,
            'status' => 200,
        ])->get();

        foreach ($photographerWorkSources as $sort => $photographerWorkSource) {

            $water1_image = \Qiniu\base64_urlSafeEncode($photographerWorkSource->deal_url);
            $sence = "1/{$photographerWork->id}";
            $xacode = User::createXacode($photographerWork->photographer_id, 'other', $sence);
            if ($xacode) {
                $water2_image = \Qiniu\base64_urlSafeEncode(
                    $xacode . '|imageMogr2/auto-orient/thumbnail/185x185!'
                );
            } else {
                $water2_image = \Qiniu\base64_urlSafeEncode(
                    $domain . '/' . config(
                        'custom.qiniu.crop_work_source_image_bg'
                    ) . '?imageMogr2/auto-orient/thumbnail/210x210!|roundPic/radius/!50p'
                );
            }

            $hanlde = [];
            $hanlde[] = "imageMogr2/auto-orient/crop/1200x" . ($photographerWorkSource->deal_height + 250);
            $hanlde[] = "|watermark/3/image/{$water1_image}/gravity/North/dx/0/dy/0/";
            $hanlde[] = "|watermark/3/image/" . base64_encode("https://file.zuopin.cloud/Fgz6Zf0EmsLVLvpCf73jBDaCPr9T") . "/gravity/South/dx/0/dy/0/";
            $hanlde[] = "|watermark/3/image/{$water2_image}/gravity/SouthEast/dx/57/dy/47/";
            $hanlde[] = "text/" . \Qiniu\base64_urlSafeEncode($photographerWork->customer_name) . "/fontsize/800/fill/" . base64_urlSafeEncode("#323232") . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/71/dy/162/";
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

            $count = PhotographerWorkSource::where('photographer_work_id', $photographerWorkSource->photographer_work_id)->where('status', 200)->count();
            $text = $count - 1 <= 0 ? '微信扫一扫，看我的全部作品' : "微信扫一扫，看剩余" . ($count - 1) . "张作品";

            $hanlde[] = "text/" . \Qiniu\base64_urlSafeEncode($text) . "/fontsize/609/fill/" . base64_urlSafeEncode("#F7F7F7") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/100/dy/78/";
            $hanlde[] = "|imageslim";

            $fops[] = implode($hanlde);

            $qrst = SystemServer::qiniuPfop(
                $bucket,
                $photographerWorkSource->key,
                $fops,
                null,
                config(
                    'app.url'
                ) . '/api/notify/qiniu/fop?photographer_work_source_id=' . $photographerWorkSource->id . '&step=2&width=1200&height=' . $photographerWorkSource->deal_height . '&sort=' . $sort,
                true
            );
            if ($qrst['err']) {
                return ErrLogServer::QiniuNotifyFop(
                    2,
                    '持久化请求失败',
                    "",
                    $photographerWorkSource,
                    $qrst['err']
                );
            }
        }

        return ['result' => true, 'msg' => "作品集"];
    }

    // 生成一张水印图根据作品集资源信息
    public static function generateOneWaterMark($photographerWorkSource, $photographerWork, $photographer)
    {
        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';
        // 生成水印图
        $water1_image = \Qiniu\base64_urlSafeEncode($photographerWorkSource->deal_url);
        $sence = "1/{$photographerWork->id}";
        $xacode = User::createXacode($photographerWork->photographer_id, 'other', $sence);
        if ($xacode) {
            $water2_image = \Qiniu\base64_urlSafeEncode(
                $xacode . '|imageMogr2/auto-orient/thumbnail/185x185!'
            );
        } else {
            $water2_image = \Qiniu\base64_urlSafeEncode(
                $domain . '/' . config(
                    'custom.qiniu.crop_work_source_image_bg'
                ) . '?imageMogr2/auto-orient/thumbnail/210x210!|roundPic/radius/!50p'
            );
        }

        $hanlde = [];
        $hanlde[] = "imageMogr2/auto-orient/crop/1200x" . ($photographerWorkSource->deal_height + 250);
        $hanlde[] = "|watermark/3/image/{$water1_image}/gravity/North/dx/0/dy/0/";
        $hanlde[] = "|watermark/3/image/" . base64_encode("https://file.zuopin.cloud/Fgz6Zf0EmsLVLvpCf73jBDaCPr9T") . "/gravity/South/dx/0/dy/0/";
        $hanlde[] = "|watermark/3/image/{$water2_image}/gravity/SouthEast/dx/57/dy/47/";
        $hanlde[] = "text/" . \Qiniu\base64_urlSafeEncode($photographerWork->customer_name) . "/fontsize/800/fill/" . base64_urlSafeEncode("#323232") . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/71/dy/162/";
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

        $count = PhotographerWorkSource::where('photographer_work_id', $photographerWorkSource->photographer_work_id)->where('status', 200)->count();
        $text = $count - 1 <= 0 ? '微信扫一扫，看我的全部作品' : "微信扫一扫，看剩余" . ($count - 1) . "张作品";

        $hanlde[] = "text/" . \Qiniu\base64_urlSafeEncode($text) . "/fontsize/609/fill/" . base64_urlSafeEncode("#F7F7F7") . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/100/dy/78/";
        $hanlde[] = "|imageslim";

        $fops[] = implode($hanlde);
        $qrst = SystemServer::qiniuPfop(
            $bucket,
            $photographerWorkSource->key,
            $fops,
            null,
            config(
                'app.url'
            ) . '/api/notify/qiniu/fop?photographer_work_source_id=' . $photographerWorkSource->id . '&step=2&width=1200&height=' . $photographerWorkSource->deal_height . '&sort=0',
            true
        );
        if ($qrst['err']) {
            return ErrLogServer::QiniuNotifyFop(
                2,
                '持久化请求失败',
                "",
                $photographerWorkSource,
                $qrst['err']
            );
        }
    }
}
