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
        'xacode',
        'xacode_hyaline',
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
     * 获取带头像的小程序码
     * @param $photographer_work_id
     * @param bool $is_hyaline 是否透明
     * @return string
     */
    public static function getXacode($photographer_work_id, $is_hyaline = true)
    {
        $photographerWork = self::find($photographer_work_id);
        if (!$photographerWork) {
            return '';
        }
        if ($is_hyaline) {
            $xacode = $photographerWork->xacode_hyaline;
        } else {
            $xacode = $photographerWork->xacode;
        }
        if ($xacode) {
            $photographer = Photographer::where('id', $photographerWork->photographer_id)->first();
            if (!$photographer) {
                return '';
            }
            if ($photographer->avatar) {
                $avatar = $photographer->avatar . '?imageMogr2/auto-orient/thumbnail/190x190!|roundPic/radius/!50p';

                return $xacode . '?imageMogr2/auto-orient|watermark/3/image/' . \Qiniu\base64_urlSafeEncode(
                        $avatar
                    ) . '/dx/115/dy/115';
            } else {
                $user = User::where('photographer_id', $photographerWork->photographer_id)->first();
                if ($user && $user->avatarUrl) {
                    $avatar = $user->avatar . '?imageMogr2/auto-orient/thumbnail/190x190!|roundPic/radius/!50p';

                    return $xacode . '?imageMogr2/auto-orient|watermark/3/image/' . \Qiniu\base64_urlSafeEncode(
                            $avatar
                        ) . '/dx/115/dy/115';
                } else {
                    return $xacode . '?imageMogr2/auto-orient';
                }
            }
        } else {
            return '';
        }
    }

    /**
     * 项目海报
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
            $response['msg'] = '用户项目不存在';

            return $response;
        }
        $photographer = User::photographer($photographer_work->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            $response['code'] = 500;
            $response['msg'] = '用户不存在';

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
            $response['msg'] = '用户不是用户';

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
        $xacode = PhotographerWork::getXacode($photographer_work_id);
        if ($xacode) {
            $xacode = $xacode . '|imageMogr2/auto-orient/thumbnail/250x250!';
        } else {
            $xacode = $domain . '/' . config(
                    'custom.qiniu.crop_work_source_image_bg'
                ) . '?imageMogr2/auto-orient/crop/250x250';
        }
        $watermark = 'watermark/3/image/' . \Qiniu\base64_urlSafeEncode($bg_img) . '/gravity/North/dx/0/dy/0';
        $watermark .= '/text/' . \Qiniu\base64_urlSafeEncode(
                '我是用户' . $photographer->name
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
     * 项目海报
     * @param $photographer_work_id 作品集id
     * @param $template_id 使用template表中的number字段
     * @return array
     */
    public static function poster2($photographer_work_id, $template_id)
    {
        $photographer_work = PhotographerWork::where(
            ['status' => 200, 'id' => $photographer_work_id]
        )->first();
        if (empty($photographer_work)) {
            return "";
        }

        $photographer = User::photographer($photographer_work->photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return "";
        }

        $user = User::where(['photographer_id' => $photographer->id])->first();
        if (!$user) {
            return "";
        }
        if ($user->identity != 1) {
            return "";
        }

        $photographer_work_source = $photographer_work->photographerWorkSources()
            ->where(
                ['status' => 200, 'type' => 'image']
            )
            ->orderBy(
                'sort',
                'asc'
            )
            ->first();

        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';

        $template = Templates::where('number', $template_id)->first();
        if (empty($template)) {
            return "";
        }
        $xacode = PhotographerWork::getXacode($photographer_work_id);
        if ($xacode) {
            $xacodeImgage = \Qiniu\base64_urlSafeEncode(
                $xacode . '|imageMogr2/auto-orient/thumbnail/250x250!'
            );
        } else {
            $xacodeImgage = \Qiniu\base64_urlSafeEncode(
                $domain . '/' . config(
                    'custom.qiniu.crop_work_source_image_bg'
                ) . '?imageMogr2/auto-orient/thumbnail/250x250!|roundPic/radius/!50p'
            );
        }

        $photographer_rank = (string)PhotographerRank::where('id', $photographer->photographer_rank_id)->value('name');
        $workName = $photographer_work->customer_name;
        $name = "{$photographer->name} · 摄影作品";
        $money = "{$photographer_work->project_amount}元 · {$photographer_work->sheets_number}张 · {$photographer_work->shooting_duration}小时";
        $datas = [
            '##money##' => "{$photographer_work->project_amount}",
            '##number##' => "{$photographer_work->sheets_number}",
            '##time##' => "{$photographer_work->shooting_duration}",
            '##customer##' => $workName,
            '##name##' => $photographer->name,
            '##title##' => "{$photographer_rank}摄像师",
        ];

        if ($photographer_work_source->deal_height > 700) {  // 长图
            $width = 1000;
            $height = $photographer_work_source->deal_height;
            $imgs = $domain . '/' . $photographer_work_source->deal_key . "?imageMogr2/auto-orient/thumbnail/{$width}x{$height}/gravity/Center/crop/1000x700|roundPic/radius/30";
        } else { // 宽图
            $imgs = $domain . '/' . $photographer_work_source->deal_key . "?imageMogr2/auto-orient/thumbnail/x600/gravity/Center/crop/!1000x700-0-0|roundPic/radius/30|imageslim";
        }

        $bg = $template->background . "?imageMogr2/auto-orient/thumbnail/1200x2133!";
        $writeBg = "https://file.zuopin.cloud/FjRG0YoL-6pTZ8lyjXbkoe4ZFddf";
        $handle = array();
        $handle[] = $bg;
        $handle[] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode($imgs) . "/gravity/South/dx/0/dy/500/";
        $handle[] = "/image/" . \Qiniu\base64_urlSafeEncode($writeBg) . "/gravity/South/dx/0/dy/200/";
        $handle[] = "/image/" . $xacodeImgage . "/gravity/SouthEast/dx/180/dy/275/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($workName) . "/fontstyle/" . base64_urlSafeEncode(
                "Bold"
            ) . "/fontsize/960/fill/" . base64_urlSafeEncode("#323232") . "/font/" . base64_urlSafeEncode(
                "Microsoft YaHei"
            ) . "/gravity/SouthWest/dx/180/dy/470/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($name) . "/fontsize/720/fill/" . base64_urlSafeEncode(
                "#646464"
            ) . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/180/dy/340/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($money) . "/fontsize/720/fill/" . base64_urlSafeEncode(
                "#646464"
            ) . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/180/dy/270/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("微信扫一扫 看完整作品") . "/fontsize/600/fill/" . base64_urlSafeEncode(
                "#FFFFFF"
            ) . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/South/dx/0/dy/86/";

        foreach ($datas as $key => $data) {
            $template->text1 = str_replace($key, $data, $template->text1);
            $template->text2 = str_replace($key, $data, $template->text2);
            $template->text3 = str_replace($key, $data, $template->text3);
            $template->text4 = str_replace($key, $data, $template->text4);
        }

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($template->text1) . "/fontstyle/" . base64_urlSafeEncode(
                "Bold"
            ) . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/font/" . base64_urlSafeEncode(
                "Microsoft YaHei"
            ) . "/gravity/NorthWest/dx/100/dy/170/";
        if ($template->text2) {
            $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($template->text2) . "/fontstyle/" . base64_urlSafeEncode(
                    "Bold"
                ) . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/font/" . base64_urlSafeEncode(
                    "Microsoft YaHei"
                ) . "/gravity/NorthWest/dx/100/dy/320/";
        }
        if ($template->text3) {
            $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($template->text3) . "/fontstyle/" . base64_urlSafeEncode(
                    "Bold"
                ) . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/font/" . base64_urlSafeEncode(
                    "Microsoft YaHei"
                ) . "/gravity/NorthWest/dx/100/dy/470/";
        }
        if ($template->text4) {
            $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($template->text4) . "/fontstyle/" . base64_urlSafeEncode(
                    "Bold"
                ) . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/font/" . base64_urlSafeEncode(
                    "Microsoft YaHei"
                ) . "/gravity/NorthWest/dx/100/dy/620/";
        }

        $url = implode($handle);
        return $url;
    }

    /**
     * 根据项目id 生成作品分享图
     * @param $photographer_work_id 作品集id
     * @return string
     */
    public function generateShare($photographer_work_id)
    {
        $work = PhotographerWork::find($photographer_work_id);
        if (empty($work)) {
            return "";
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
            return "";
        }

        // 拿到七牛url
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets['zuopin']['domain'] ?? '';
        // 背景图
        //    $whiteBg = $domain . '/FtSr3gPOeI8CjSgh5fBkeHaIsJnm?imageMogr2/auto-orient/thumbnail/1199x959!';

        //     $whiteBg = $firstPhoto->deal_url .'?imageMogr2/auto-orient/thumbnail/1199x959!';

        // 上面图
        $sharePhoto = $firstPhoto->deal_url . "?imageMogr2/auto-orient/gravity/Center/crop/1199x959|roundPic/radius/50";
        $bg = $domain . "/FuF4NBlMvQOgHAtbvGWaH5qelepM?imageMogr2/auto-orient/thumbnail/1199x579!";
        $handleUrl = array();
        $handleUrl[0] = $sharePhoto;
        $handleUrl[1] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode($bg) . "/gravity/North/dx/0/dy/0";
        $handleUrl[2] = "/text/" . \Qiniu\base64_urlSafeEncode(
                $customer_name
            ) . "/fontsize/1997/fill/" . base64_urlSafeEncode("#FEFEFE") . "/fontstyle/" . base64_urlSafeEncode(
                "Bold"
            ) . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/NorthWest/dx/50/dy/70";

        $handleUrl[3] = "/text/" . \Qiniu\base64_urlSafeEncode(
                "点击看项目金额"
            ) . "/fontsize/1399/fill/" . base64_urlSafeEncode("#FEFEFE") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/NorthWest/dx/50/dy/200";
//        $handleUrl[3] = "/text/" . \Qiniu\base64_urlSafeEncode($buttonText) . "/fontsize/1140/fill/" . base64_urlSafeEncode(
//                "#969696"
//            ) . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/South/dx/0/dy/20";

        //array_shift($handleUrl);

        //return "https://file.zuopin.cloud/FtSr3gPOeI8CjSgh5fBkeHaIsJnm?imageMogr2/auto-orient/thumbnail/1200x960!" . implode("", $handleUrl);
        return implode("", $handleUrl);
    }

}
