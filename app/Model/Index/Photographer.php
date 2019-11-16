<?php

namespace App\Model\Index;

use App\Model\Admin\SystemArea;
use Illuminate\Database\Eloquent\Model;

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
     * 摄影师海报
     * @param $photographer_id
     * @return array
     */
    public static function poster($photographer_id)
    {
        $response = [];
        $photographer = User::photographer($photographer_id);
        if (!$photographer || $photographer->status != 200) {
            $response['code'] = 500;
            $response['msg'] = '摄影师不存在';
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
        $url = $domain.'/'.config('custom.qiniu.crop_work_source_image_bg');
        $deals = [];
        $deals[] = 'imageMogr2/crop/1200x2133';
        if ($photographer->bg_img) {
            $photographer->bg_img = $photographer->bg_img.'?imageMogr2/auto-orient/thumbnail/1200x/gravity/Center/crop/!1200x600-0-0|imageslim';
        } else {
            $photographer->bg_img = config('app.url').'/'.'images/poster_bg.jpg';
        }
        if ($photographer->avatar) {
            $photographer->avatar = $photographer->avatar.'?imageMogr2/thumbnail/300x300!|roundPic/radius/!50p|imageslim';
        } else {
            $photographer->avatar = $domain.'/'.config(
                    'custom.qiniu.avatar'
                ).'?imageMogr2/thumbnail/300x300!|roundPic/radius/!50p|imageslim';
        }
        if ($user->xacode) {
            $user->xacode = $user->xacode.'|imageMogr2/thumbnail/250x250!';
        } else {
            $user->xacode = $domain.'/'.config('custom.qiniu.crop_work_source_image_bg').'?imageMogr2/crop/250x250';
        }
        $photographer_city = (string)SystemArea::where('id', $photographer->city)->value('short_name');
        $photographer_rank = (string)PhotographerRank::where('id', $photographer->photographer_rank_id)->value('name');
        $photographer_works_count = $photographer->photographerWorks()->where('status', 200)->count();
        $photographer_works = $photographer->photographerWorks()->where('status', 200)->orderBy(
            'created_at',
            'desc'
        )->limit(4)->get()->toArray();
        if ($photographer_works_count > 4) {
            $text1 = $photographer_works[0]['customer_name'].'·'.$photographer_works[1]['customer_name'];
            $text2 = $photographer_works[2]['customer_name'].'·'.$photographer_works[3]['customer_name'];
            $text3 = '……';
        } elseif ($photographer_works_count == 4) {
            $text1 = $photographer_works[0]['customer_name'].'·'.$photographer_works[1]['customer_name'];
            $text2 = $photographer_works[2]['customer_name'].'·'.$photographer_works[3]['customer_name'];
            $text3 = '';
        } elseif ($photographer_works_count == 3) {
            $text1 = $photographer_works[0]['customer_name'].'·'.$photographer_works[1]['customer_name'];
            $text2 = $photographer_works[2]['customer_name'];
            $text3 = '';
        } elseif ($photographer_works_count == 2) {
            $text1 = $photographer_works[0]['customer_name'].'·'.$photographer_works[1]['customer_name'];
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
        $watermark = 'watermark/3/image/'.\Qiniu\base64_urlSafeEncode($photographer->bg_img).'/gravity/North/dx/0/dy/0';
        $watermark .= '/image/'.\Qiniu\base64_urlSafeEncode($photographer->avatar).'/gravity/North/dx/0/dy/450';
        $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                'Hi！我是摄影师'.$photographer->name
            ).'/fontsize/1500/fill/'.\Qiniu\base64_urlSafeEncode('#313131').'/gravity/North/dx/0/dy/900';
        $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                '坐标'.$photographer_city.'·'.'擅长'.$photographer_rank.'摄影'
            ).'/fontsize/1000/fill/'.\Qiniu\base64_urlSafeEncode('#696969').'/gravity/North/dx/0/dy/1060';
        if ($text1) {
            $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                    $text1
                ).'/fontsize/800/fill/'.\Qiniu\base64_urlSafeEncode('#999999').'/gravity/North/dx/0/dy/1250';
        }
        if ($text2) {
            $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                    $text2
                ).'/fontsize/800/fill/'.\Qiniu\base64_urlSafeEncode('#999999').'/gravity/North/dx/0/dy/1330';
        }
        if ($text3) {
            $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                    $text3
                ).'/fontsize/800/fill/'.\Qiniu\base64_urlSafeEncode('#999999').'/gravity/North/dx/0/dy/1410';
        }
        $watermark .= '/image/'.\Qiniu\base64_urlSafeEncode($user->xacode).'/gravity/North/dx/0/dy/1630';
        $watermark .= '/text/'.\Qiniu\base64_urlSafeEncode(
                '微信扫一扫 看我的作品'
            ).'/fontsize/700/fill/'.\Qiniu\base64_urlSafeEncode('#4E4E4E').'/gravity/North/dx/0/dy/1950';
        $deals[] = $watermark;
        $url .= '?'.implode('|', $deals);
        $response['code'] = 200;
        $response['msg'] = 'ok';
        $response['url'] = $url;

        return $response;
    }
}
