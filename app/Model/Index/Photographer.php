<?php

namespace App\Model\Index;

use App\Model\Admin\SystemArea;
use App\Servers\ErrLogServer;
use App\Servers\SystemServer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use function Qiniu\base64_urlSafeEncode;

class Photographer extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'gender',
        'avatar',
        'bg_img',
        'province',
        'city',
        'area',
        'photographer_rank_id',
        'wechat',
        'mobile',
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
            'name',
            'gender',
            'avatar',
            'bg_img',
            'province',
            'city',
            'area',
            'photographer_rank_id',
            'wechat',
            'mobile',
            'created_at',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function photographerWorks()
    {
        return $this->hasMany(PhotographerWork::class);
    }

    /**
     * 获取带头像的小程序码
     * @param $photographer_id
     * @param bool $is_hyaline 是否透明
     * @return string
     */
    public static function xacode($photographer_id, $is_hyaline = true)
    {
        $photographer = self::find($photographer_id);
        if (!$photographer) {
            return '';
        }
        if ($is_hyaline) {
            $xacode = $photographer->xacode_hyaline;
        } else {
            $xacode = $photographer->xacode;
        }
        if ($xacode) {
            if ($photographer->avatar) {
                $avatar = $photographer->avatar . '?imageMogr2/auto-orient/thumbnail/190x190!|roundPic/radius/!50p';

                return $xacode . '?imageMogr2/auto-orient|watermark/3/image/' . \Qiniu\base64_urlSafeEncode(
                        $avatar
                    ) . '/dx/115/dy/115';
            } else {
                $user = User::where('photographer_id', $photographer_id)->first();
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
     * 用户海报
     * @param $photographer_id
     * @return array
     */
    public static function poster($photographer_id)
    {
        $response = [];
        $photographer = User::photographer($photographer_id);
        if (!$photographer || $photographer->status != 200) {
            $response['code'] = 500;
            $response['msg'] = '用户不存在';

            return $response;
        }
        $user = User::where(['photographer_id' => $photographer_id])->first();
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
        if ($photographer->bg_img) {
            $photographer->bg_img = $photographer->bg_img . '?imageMogr2/auto-orient/thumbnail/1200x/gravity/Center/crop/!1200x600-0-0|imageslim';
        } else {
            $photographer->bg_img = config('app.url') . '/' . 'images/poster_bg.jpg';
        }
        if ($photographer->avatar) {
            $photographer->avatar = $photographer->avatar . '?imageMogr2/auto-orient/thumbnail/300x300!|roundPic/radius/!50p|imageslim';
        } else {
            $photographer->avatar = $domain . '/' . config(
                    'custom.qiniu.avatar'
                ) . '?imageMogr2/auto-orient/thumbnail/300x300!|roundPic/radius/!50p|imageslim';
        }
        $xacode = Photographer::xacode($photographer->id);
        if ($xacode) {
            $xacode = $xacode . '|imageMogr2/auto-orient/thumbnail/250x250!';
        } else {
            $xacode = $domain . '/' . config(
                    'custom.qiniu.crop_work_source_image_bg'
                ) . '?imageMogr2/auto-orient/crop/250x250';
        }
        $photographer_city = (string)SystemArea::where('id', $photographer->city)->value('short_name');
        $photographer_rank = (string)PhotographerRank::where('id', $photographer->photographer_rank_id)->value('name');
        $photographer_works_count = $photographer->photographerWorks()->where('status', 200)->count();
        $photographer_works = $photographer->photographerWorks()->where('status', 200)->orderBy(
            'roof',
            'desc'
        )->orderBy(
            'created_at',
            'desc'
        )->orderBy(
            'id',
            'desc'
        )->limit(4)->get()->toArray();
        if ($photographer_works_count > 4) {
            $text1 = $photographer_works[0]['customer_name'] . '·' . $photographer_works[1]['customer_name'];
            $text2 = $photographer_works[2]['customer_name'] . '·' . $photographer_works[3]['customer_name'];
            $text3 = '……';
        } elseif ($photographer_works_count == 4) {
            $text1 = $photographer_works[0]['customer_name'] . '·' . $photographer_works[1]['customer_name'];
            $text2 = $photographer_works[2]['customer_name'] . '·' . $photographer_works[3]['customer_name'];
            $text3 = '';
        } elseif ($photographer_works_count == 3) {
            $text1 = $photographer_works[0]['customer_name'] . '·' . $photographer_works[1]['customer_name'];
            $text2 = $photographer_works[2]['customer_name'];
            $text3 = '';
        } elseif ($photographer_works_count == 2) {
            $text1 = $photographer_works[0]['customer_name'] . '·' . $photographer_works[1]['customer_name'];
            $text2 = '';
            $text3 = '';
        } elseif ($photographer_works_count == 1) {
            $text1 = $photographer_works[0]['customer_name'];
            $text2 = '';
            $text3 = '';
        } else {
            $text1 = '';
            $text2 = '';
            $text3 = '';
        }
        $watermark = 'watermark/3/image/' . \Qiniu\base64_urlSafeEncode($photographer->bg_img) . '/gravity/North/dx/0/dy/0';
        $watermark .= '/image/' . \Qiniu\base64_urlSafeEncode($photographer->avatar) . '/gravity/North/dx/0/dy/450';
        $watermark .= '/text/' . \Qiniu\base64_urlSafeEncode(
                'Hi！我是用户' . $photographer->name
            ) . '/fontsize/1500/fill/' . \Qiniu\base64_urlSafeEncode('#313131') . '/gravity/North/dx/0/dy/900';
        $watermark .= '/text/' . \Qiniu\base64_urlSafeEncode(
                '坐标' . $photographer_city . '·' . '擅长' . $photographer_rank . '摄影'
            ) . '/fontsize/1000/fill/' . \Qiniu\base64_urlSafeEncode('#696969') . '/gravity/North/dx/0/dy/1060';
        if ($text1) {
            $watermark .= '/text/' . \Qiniu\base64_urlSafeEncode(
                    $text1
                ) . '/fontsize/800/fill/' . \Qiniu\base64_urlSafeEncode('#999999') . '/gravity/North/dx/0/dy/1250';
        }
        if ($text2) {
            $watermark .= '/text/' . \Qiniu\base64_urlSafeEncode(
                    $text2
                ) . '/fontsize/800/fill/' . \Qiniu\base64_urlSafeEncode('#999999') . '/gravity/North/dx/0/dy/1330';
        }
        if ($text3) {
            $watermark .= '/text/' . \Qiniu\base64_urlSafeEncode(
                    $text3
                ) . '/fontsize/800/fill/' . \Qiniu\base64_urlSafeEncode('#999999') . '/gravity/North/dx/0/dy/1410';
        }
        $watermark .= '/image/' . \Qiniu\base64_urlSafeEncode($xacode) . '/gravity/North/dx/0/dy/1630';
        $watermark .= '/text/' . \Qiniu\base64_urlSafeEncode(
                '微信扫一扫 看我的作品'
            ) . '/fontsize/700/fill/' . \Qiniu\base64_urlSafeEncode('#4E4E4E') . '/gravity/North/dx/0/dy/1950';
        $deals[] = $watermark;
        $url .= '?' . implode('|', $deals);
        $response['code'] = 200;
        $response['msg'] = 'ok';
        $response['url'] = $url;

        return $response;
    }

    public static function poster2($photographer_id)
    {
        $data = [
            'url1' => '',
            'url2' => '',
            'url3' => '',
        ];
        $response = [];
        $photographer = User::photographer($photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return $data;
        }
        $user = User::where(['photographer_id' => $photographer_id])->first();
        if (!$user) {
            return $data;
        }
        if ($user->identity != 1) {
            return $data;
        }

        $bucket = 'zuopin';
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets[$bucket]['domain'] ?? '';

        $xacode = Photographer::xacode($photographer_id);
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

        $photographer_city = (string)SystemArea::where('id', $photographer->city)->value('short_name');
        $photographer_rank = (string)PhotographerRank::where('id', $photographer->photographer_rank_id)->value('name');
        $photographer_works_count = $photographer->photographerWorks()->where('status', 200)->count();
        $photographer_works = $photographer->photographerWorks()->where('status', 200)->orderBy(
            'roof',
            'desc'
        )->orderBy(
            'created_at',
            'desc'
        )->orderBy(
            'id',
            'desc'
        )->limit(4)->get()->toArray();

        $text = [];
        foreach ($photographer_works as $photographer_work) {
            $text[] = $photographer_work['customer_name'];
        }

        $data = [];
        $data['url1'] = self::getPersonStyle1(
            $xacodeImgage,
            $photographer,
            $photographer_city,
            $photographer_rank,
            $text
        );
        $data['url2'] = self::getPersonStyle2(
            $xacodeImgage,
            $photographer,
            $photographer_city,
            $photographer_rank,
            $text
        );
        $data['url3'] = self::getPersonStyle3(
            $xacodeImgage,
            $photographer,
            $photographer_city,
            $photographer_rank,
            $text
        );

        return $data;
    }

    private static function getPersonStyle1($xacodeImgage, $photographer, $photographer_city, $photographer_rank, $text)
    {
        $bg = "https://file.zuopin.cloud/FuELuuJ-zIV2QxzmDZrSCPesst51?imageMogr2/auto-orient/thumbnail/1200x2133!";
        $handle = array();
        $handle[] = $bg;
        $handle[] = "|watermark/3/image/" . base64_urlSafeEncode(
                "https://file.zuopin.cloud/FqRtRSleuVUJEN61BSRXvszMmzTH"
            ) . "/gravity/South/dx/0/dy/0/";
        $handle[] = "image/" . $xacodeImgage . "/gravity/SouthEast/dx/100/dy/325/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("微信扫一扫 看全部作品") . "/fontsize/720/fill/" . base64_urlSafeEncode(
                "#F7F7F7"
            ) . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/141/dy/334/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode(
                $photographer->name
            ) . "/fontsize/1300/fill/" . base64_urlSafeEncode("#323232") . "/fontstyle/" . base64_urlSafeEncode(
                "Bold"
            ) . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/98/dy/520/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode(
                $photographer_city . ' · ' . $photographer_rank . '用户'
            ) . "/fontsize/720/fill/" . base64_urlSafeEncode("#646464") . "/font/" . base64_urlSafeEncode(
                "微软雅黑"
            ) . "/gravity/SouthWest/dx/99/dy/450/";

        // 最下面那行
        $footerFont = mb_substr(implode(' · ', $text), 0, 34);
        mb_strlen(implode(' · ', $text)) > 34 ? $footerFont .= '…' : "";

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($footerFont) . "/fontsize/720/fill/" . base64_urlSafeEncode(
                "#969696"
            ) . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/99/dy/90/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("Hi!") . "/fontsize/2000/fill/" . base64_urlSafeEncode(
                "#FFFFFF"
            ) . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/font/" . base64_urlSafeEncode(
                "Microsoft YaHei"
            ) . "/gravity/NorthWest/dx/101/dy/180/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("我是用户") . "/fontsize/2000/fill/" . base64_urlSafeEncode(
                "#FFFFFF"
            ) . "/fontstyle/" . base64_urlSafeEncode("Bold") . "/font/" . base64_urlSafeEncode(
                "Microsoft YaHei"
            ) . "/gravity/NorthWest/dx/101/dy/330/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode(
                $photographer->name
            ) . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/fontstyle/" . base64_urlSafeEncode(
                "Bold"
            ) . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/NorthWest/dx/101/dy/480/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode(
                'Base' . $photographer_city
            ) . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/fontstyle/" . base64_urlSafeEncode(
                "Bold"
            ) . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/West/dx/101/dy/-220/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode(
                '擅长' . $photographer_rank . '摄像'
            ) . "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") . "/fontstyle/" . base64_urlSafeEncode(
                "Bold"
            ) . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/West/dx/101/dy/-70/";
        $handle[] = "|imageslim";

        return implode($handle);
    }

    private static function getPersonStyle2($xacodeImgage, $photographer, $photographer_city, $photographer_rank, $text)
    {

        $photographerBgImg = "";

        if ($photographer->bg_img) {
            $photographerBgImg = $photographer->bg_img . '?imageMogr2/auto-orient/thumbnail/!1200x1483r/gravity/Center/crop/1200x1483|imageslim';
        } else {
            $photographerBgImg = "https://file.zuopin.cloud/FjeXtrkXjHpqKbEFLvt4ZeadsYZy?imageMogr2/auto-orient/thumbnail/!1200x1483r|imageslim";
        }

        $bg = "https://file.zuopin.cloud/FuELuuJ-zIV2QxzmDZrSCPesst51?imageMogr2/auto-orient/thumbnail/1200x2133!";
        $handle = array();
        $handle[] = $bg;

        $handle[] = "|watermark/3/image/" . base64_urlSafeEncode($photographerBgImg) . "/gravity/North/dx/0/dy/0/";
        $handle[] = "image/" . base64_urlSafeEncode(
                "https://file.zuopin.cloud/FqRtRSleuVUJEN61BSRXvszMmzTH"
            ) . "/gravity/South/dx/0/dy/0/";

        $handle[] = "image/" . $xacodeImgage . "/gravity/SouthEast/dx/100/dy/325/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("微信扫一扫 看全部作品") . "/fontsize/720/fill/" . base64_urlSafeEncode(
                "#F7F7F7"
            ) . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/140/dy/333/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode(
                $photographer->name
            ) . "/fontsize/1300/fill/" . base64_urlSafeEncode("#323232") . "/fontstyle/" . base64_urlSafeEncode(
                "Bold"
            ) . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/100/dy/520/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode(
                $photographer_city . ' · ' . $photographer_rank . '用户'
            ) . "/fontsize/720/fill/" . base64_urlSafeEncode("#646464") . "/font/" . base64_urlSafeEncode(
                "微软雅黑"
            ) . "/gravity/SouthWest/dx/100/dy/450/";

        // 最下面那行
        $footerFont = mb_substr(implode(' · ', $text), 0, 34);
        mb_strlen(implode(' · ', $text)) > 34 ? $footerFont .= '…' : "";

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($footerFont) . "/fontsize/720/fill/" . base64_urlSafeEncode(
                "#969696"
            ) . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/100/dy/90/";
        $handle[] = "|imageslim";

        return implode($handle);
    }

    private static function getPersonStyle3($xacodeImgage, $photographer, $photographer_city, $photographer_rank, $text)
    {
        $bg = "https://file.zuopin.cloud/FuELuuJ-zIV2QxzmDZrSCPesst51?imageMogr2/auto-orient/thumbnail/1200x2133!";
        $handle = array();
        $handle[] = $bg;
        $handle[] = "|watermark/3/image/" . base64_urlSafeEncode(
                "https://file.zuopin.cloud/FqRtRSleuVUJEN61BSRXvszMmzTH"
            ) . "/gravity/South/dx/0/dy/0/";
        $handle[] = "image/" . $xacodeImgage . "/gravity/SouthEast/dx/100/dy/325/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("微信扫一扫 看全部作品") . "/fontsize/720/fill/" . base64_urlSafeEncode(
                "#F7F7F7"
            ) . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/140/dy/333/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode(
                $photographer->name
            ) . "/fontsize/1300/fill/" . base64_urlSafeEncode("#323232") . "/fontstyle/" . base64_urlSafeEncode(
                "Bold"
            ) . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthWest/dx/100/dy/520/";
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode(
                $photographer_city . ' · ' . $photographer_rank . '用户'
            ) . "/fontsize/720/fill/" . base64_urlSafeEncode("#646464") . "/font/" . base64_urlSafeEncode(
                "微软雅黑"
            ) . "/gravity/SouthWest/dx/100/dy/450/";

        // 最下面那行
        $footerFont = mb_substr(implode(' · ', $text), 0, 34);
        mb_strlen(implode(' · ', $text)) > 34 ? $footerFont .= '…' : "";

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($footerFont) . "/fontsize/720/fill/" . base64_urlSafeEncode(
                "#969696"
            ) . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthWest/dx/100/dy/90/";
        $endKey = count($text);

        $indexPos = 180;
        foreach ($text as $key => $item) {
            $handle[] = "text/" . \Qiniu\base64_urlSafeEncode($item) .
                "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") .
                "/fontstyle/" . base64_urlSafeEncode("Bold") .
                "/font/" . base64_urlSafeEncode("Microsoft YaHei") .
                "/gravity/NorthWest/dx/100/dy/" . ($indexPos + ($key * 150)) . "/";
        }

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("……") .
            "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") .
            "/fontstyle/" . base64_urlSafeEncode("Bold") .
            "/font/" . base64_urlSafeEncode("Microsoft YaHei") .
            "/gravity/NorthWest/dx/100/dy/" . ($indexPos + ($endKey * 160)) . "/";

        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("都是我拍的") .
            "/fontsize/2000/fill/" . base64_urlSafeEncode("#FFFFFF") .
            "/fontstyle/" . base64_urlSafeEncode("Bold") .
            "/font/" . base64_urlSafeEncode("Microsoft YaHei") .
            "/gravity/West/dx/100/dy/80/";

        $handle[] = "|imageslim";

        return implode($handle);
    }


    /**
     * 生成小程序卡片分享图
     * @param $photographer_id
     * @return string
     */
    public function generateShare($photographer_id)
    {
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets['zuopin']['domain'] ?? '';
        // 白背景图
        $whiteBg = $domain . '/FtSr3gPOeI8CjSgh5fBkeHaIsJnm?imageMogr2/auto-orient/thumbnail/1200x960!';
        // 黑背景图
        $blackBgs = [];
        $blackBg = $domain . '/FtXkbly4Qu-tEeiBiolLj-FFPXeo?imageMogr2/auto-orient/thumbnail/383x320!';
        $blackBgs = array_fill(0, 6, $blackBg);

        $photographer = User::photographer($photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return "";
        }
        $workIds = PhotographerWork::where('photographer_id', $photographer_id)
            ->where('status', 200)->get()->pluck('id');
        $resources = PhotographerWorkSource::where(['status' => 200])
            ->where('type', 'image')
            ->whereIn('photographer_work_id', $workIds)
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        if (empty($resources)) {
            return "";
        }

        $buttonText = SystemArea::find($photographer->province)->name . ' · ' . PhotographerRank::find(
                $photographer->photographer_rank_id
            )->name . '用户';

        $resourceId = 0;
        foreach ($resources as $key => $resource) {
            if (empty($resource->deal_width) || empty($resource->deal_height)) {
                $response = SystemServer::request('GET', $resource->deal_url . '?imageInfo');
                if (isset($response['code']) && $response['code'] == 200) {
                    $resource->deal_width = $response['data']['width'];
                    $resource->deal_height = $response['data']['height'];
                } else {
                    $response = SystemServer::request('GET', $resource->url . '?imageInfo');
                    if (isset($response['code']) && $response['code'] == 200) {
                        $resource->deal_width = 1200;
                        $resource->deal_height = $response['data']['height'];
                    } else {
                        \Log::debug($response['msg']);
                    }
                }
            }
            $resourceId = $resource->id;
            if ($resource->deal_width < $resource->deal_height) {  // 长图
                $width = 380;
                $height = $resource->deal_height;
                $imgs = $domain . '/' . $resource->deal_key . "?imageMogr2/auto-orient/thumbnail/{$width}x{$height}/gravity/Center/crop/382x320";
            } else { // 宽图
                $width = $resource->deal_width;
                $height = $resource->deal_height;
                $imgs = $domain . '/' . $resource->deal_key . "?imageMogr2/auto-orient/thumbnail/{$width}x{$height}/gravity/Center/crop/382x320";
            }
            $blackBgs[$key] = $imgs;
        }

        $handleUrl = array();
        $handleUrl[] = $whiteBg;
        $handleUrl[] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode($blackBgs[0]) . "/gravity/NorthWest/dx/0/dy/0";
        $handleUrl[] = "/image/" . \Qiniu\base64_urlSafeEncode($blackBgs[1]) . "/gravity/NorthWest/dx/409/dy/0";
        $handleUrl[] = "/image/" . \Qiniu\base64_urlSafeEncode($blackBgs[2]) . "/gravity/NorthWest/dx/817/dy/0";
        $handleUrl[] = "/image/" . \Qiniu\base64_urlSafeEncode($blackBgs[3]) . "/gravity/NorthWest/dx/0/dy/340";
        $handleUrl[] = "/image/" . \Qiniu\base64_urlSafeEncode($blackBgs[4]) . "/gravity/NorthWest/dx/409/dy/340";
        $handleUrl[] = "/image/" . \Qiniu\base64_urlSafeEncode($blackBgs[5]) . "/gravity/NorthWest/dx/817/dy/340";
        $handleUrl[] = "/text/" . \Qiniu\base64_urlSafeEncode(
                $photographer->name
            ) . "/fontsize/1700/fill/" . base64_urlSafeEncode("#323232") . "/fontstyle/" . base64_urlSafeEncode(
                "Bold"
            ) . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/North/dx/0/dy/743";
        $handleUrl[] = "/text/" . \Qiniu\base64_urlSafeEncode($buttonText) . "/fontsize/1000/fill/" . base64_urlSafeEncode(
                "#969696"
            ) . "/gravity/North/dx/0/dy/886";

        array_shift($handleUrl);

        return "https://file.zuopin.cloud/FtSr3gPOeI8CjSgh5fBkeHaIsJnm?imageMogr2/auto-orient/thumbnail/1200x960!" . implode("", $handleUrl);
    }


}
