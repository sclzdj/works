<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class PhotographerGather extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'photographer_id',
        'photographer_gather_info_id',
        'name',
        'xacode',
        'xacode_hyaline',
        'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * 允许查询的字段
     * @return array
     */
    public static function allowFields()
    {
        return [
            'id',
            'photographer_id',
            'photographer_gather_info_id',
            'name',
            'created_at',
        ];
    }

    /**
     * 获取带头像的小程序码
     * @param $photographer_gather_id
     * @param bool $is_hyaline 是否透明
     * @return string
     */
    public static function getXacode($photographer_gather_id, $is_hyaline = true)
    {
        $photographerGather = self::find($photographer_gather_id);
        if (!$photographerGather) {
            return '';
        }
        if ($is_hyaline) {
            $xacode = $photographerGather->xacode_hyaline;
        } else {
            $xacode = $photographerGather->xacode;
        }
        if ($xacode) {
            $photographer = Photographer::where('id', $photographerGather->photographer_id)->first();
            if (!$photographer) {
                return '';
            }
            if ($photographer->avatar) {
                $avatar = $photographer->avatar . '?imageMogr2/auto-orient/thumbnail/190x190!|roundPic/radius/!50p';

                return $xacode . '?imageMogr2/auto-orient|watermark/3/image/' . \Qiniu\base64_urlSafeEncode(
                        $avatar
                    ) . '/dx/115/dy/115';
            } else {
                $user = User::where('photographer_id', $photographerGather->photographer_id)->first();
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
     * 合集海报
     * @param $photographer_gather_id
     * @return array
     */
    public static function poster($photographer_gather_id)
    {

    }
}