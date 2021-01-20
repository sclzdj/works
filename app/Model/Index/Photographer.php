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
        'mobilecontact',
        'email',
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
            'level',
            'vip_expiretime',
            'mobile',
            'mobilecontact',
            'invite_times',
            'email',
            'share_xacode',
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
    public static function getXacode($photographer_id, $is_hyaline = true, $xacode="")
    {
        $photographer = self::find($photographer_id);
        if (!$photographer) {
            return '';
        }
        if (!$xacode){
            if ($is_hyaline) {
                $xacode = $photographer->xacode_hyaline;
            } else {
                $xacode = $photographer->xacode;
            }
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
            $response['msg'] = '用户不是摄影师';

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
        $xacode = Photographer::getXacode($photographer->id);
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
                'Hi！我是摄影师' . $photographer->name
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

        $xacode = Photographer::getXacode($photographer_id);
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
                $photographer_city . ' · ' . $photographer_rank . '摄影师'
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
        $handle[] = "text/" . \Qiniu\base64_urlSafeEncode("我是摄影师") . "/fontsize/2000/fill/" . base64_urlSafeEncode(
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
                $photographer_city . ' · ' . $photographer_rank . '摄影师'
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
                $photographer_city . ' · ' . $photographer_rank . '摄影师'
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
    public function generateShare($photographer_id, $photographer_gather_id=null)
    {
        $buckets = config('custom.qiniu.buckets');
        $domain = $buckets['zuopin']['domain'] ?? '';
        // 白背景图
        $whiteBg = $domain . '/FtSr3gPOeI8CjSgh5fBkeHaIsJnm?imageMogr2/auto-orient/thumbnail/600x480!';
        // 黑背景图
        $blackBgs = [];

        $photographer = User::photographer($photographer_id);
        if (!$photographer || $photographer->status != 200) {
            return "";
        }
        if (!$photographer_gather_id){
            $workIds = $photographer->photographerWorks()
                ->where('status', 200)->orderBy(
                    'roof',
                    'desc'
                )->orderBy(
                    'created_at',
                    'desc'
                )->orderBy(
                    'id',
                    'desc'
                )->limit(3)->pluck('id');

            $projectSum = PhotographerWork::where(['status' => 200])
                ->where('photographer_id', $photographer_id)
                ->count();

            $watername = $photographer->name;
        }else{
            $workIds = PhotographerGatherWork::where(['photographer_gather_id' => $photographer_gather_id])->orderBy('sort', 'id desc')->limit(3)->pluck('photographer_work_id');

            $projectSum = PhotographerGatherWork::where(['photographer_gather_id' => $photographer_gather_id])->count();

            $pg =  PhotographerGather::where(['id' => $photographer_gather_id])->first();
            $watername = $pg->name;

        }


        $resources = [];
        $sourcecount = 0;
        foreach ($workIds as $workId) {
            $resource = PhotographerWorkSource::where(['status' => 200])
                ->where('type', 'image')
                ->where('photographer_work_id', $workId)
                ->orderBy(
                    'sort',
                    'asc'
                )
                ->first();
            $resources[] = $resource;
        }

        $sourcecount = PhotographerGather::getGatherWorkSourcescount($photographer_gather_id);

        if (empty($resources)) {
            return "";
        }

        if (isset($resources[0])) {
            $blackBgs[0] = $resources[0]->deal_url . '?imageMogr2/auto-orient/thumbnail/!385x410r/gravity/Center/crop/385x410|roundPic/radius/25';
        } else {
            $blackBgs[0] = "https://file.zuopin.cloud/Fu2bJVMdZriF1vS1_f4mSvxGyXdk?imageMogr2/auto-orient/thumbnail/!385x410r";
        }

        if (isset($resources[1])) {
            $blackBgs[1] = $resources[1]->deal_url . '?imageMogr2/auto-orient/thumbnail/!195x195r/gravity/Center/crop/195x195|roundPic/radius/25';
        } else {
            $blackBgs[1] = "https://file.zuopin.cloud/Fu2bJVMdZriF1vS1_f4mSvxGyXdk?imageMogr2/auto-orient/thumbnail/!195x195r";
        }

        if (isset($resources[2])) {
            $blackBgs[2] = $resources[2]->deal_url . '?imageMogr2/auto-orient/thumbnail/!195x195r/gravity/Center/crop/195x195|roundPic/radius/25';
        } else {
            $blackBgs[2] = "https://file.zuopin.cloud/Fu2bJVMdZriF1vS1_f4mSvxGyXdk?imageMogr2/auto-orient/thumbnail/!195x195r";
        }



        $startPoint = $this->calcWaterText($watername);

        $handleUrl = array();
        $handleUrl[] = $whiteBg;
        $handleUrl[] = "|watermark/3/image/" . \Qiniu\base64_urlSafeEncode($blackBgs[0]) . "/gravity/NorthWest/dx/0/dy/0";
        $handleUrl[] = "/image/" . \Qiniu\base64_urlSafeEncode($blackBgs[1]) . "/gravity/NorthWest/dx/405/dy/0";
        $handleUrl[] = "/image/" . \Qiniu\base64_urlSafeEncode($blackBgs[2]) . "/gravity/NorthWest/dx/405/dy/215";
//        $handleUrl[] = "/text/" . \Qiniu\base64_urlSafeEncode(
//                $watername
//            ) . "/fontsize/700/fill/" . base64_urlSafeEncode("#969696") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthEast/dx/6/dy/0";

//        $handleUrl[] = "/3/image/" . \Qiniu\base64_urlSafeEncode(
//                "https://file.zuopin.cloud/FvHauIYQj3IAF-2t4Q6KSDBNXO58"
//            ) . "/font/" . base64_urlSafeEncode("微软雅黑") . "/gravity/SouthEast/dx/" . $startPoint . "/dy/10";

        $handleUrl[] = "/text/" . \Qiniu\base64_urlSafeEncode(
                $projectSum . '个项目 · ' . $sourcecount . '个作品'
            ) . "/fontsize/700/fill/" . base64_urlSafeEncode("#969696") . "/font/" . base64_urlSafeEncode("Microsoft YaHei") . "/gravity/SouthEast/dx/6/dy/0/3";


        return implode("", $handleUrl);
    }

    private function calcWaterText($customer_name)
    {
        $fistX = 0;
        for ($i = 0; $i < mb_strlen($customer_name); $i++) {
            $char = mb_substr($customer_name, $i, 1);
            if (ord($char) > 126) {
                $fistX += 40;
            } else {
                $fistX += 20;
            }
        }

        return $fistX;
    }


}
