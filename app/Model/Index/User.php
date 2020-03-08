<?php

namespace App\Model\Index;

use App\Servers\SystemServer;
use App\Servers\WechatServer;
use Illuminate\Notifications\Notifiable;#必须引用
use Illuminate\Foundation\Auth\User as Authenticatable;#必须引用
use Intervention\Image\Facades\Image;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Tymon\JWTAuth\Contracts\JWTSubject;#必须引用

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    #必须定义
    #获取存储在JWT主题声明中的的标识符，一般就是主键
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    #必须定义
    #返回一个键值数组，包含添加到JWT的任何自定义声明
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'password',
        'nickname',
        'phoneNumber',
        'purePhoneNumber',
        'countryCode',
        'avatar',
        'gender',
        'country',
        'province',
        'city',
        'photographer_id',
        'identity',
        'is_formal_photographer',
        'is_wx_authorize',
        'is_wx_get_phone_number',
        'openid',
        'unionid',
        'gh_openid',
        'session_key',
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
            'username',
            'nickname',
            'phoneNumber',
            'purePhoneNumber',
            'countryCode',
            'avatar',
            'gender',
            'country',
            'province',
            'city',
            'created_at',
        ];
    }

    /**
     * 关联的摄影师
     * @param null $photographer_id
     * @return mixed
     */
    public static function photographer($photographer_id = null, $guard = null)
    {
        if (empty($photographer_id)) {
            $photographer_id = auth($guard)->user()->photographer_id;
        }

        return Photographer::find($photographer_id);
    }

    /**
     * 创建用户预设一些东西
     */
    public static function presetCreate()
    {
        //先预设一个摄影师
        $photographer = Photographer::create();
        //再预设一个作品集
        $photographer_work = PhotographerWork::create();
        $photographer_work->photographer_id = $photographer->id;
        $photographer_work->save();

        return [
            'photographer_id' => $photographer->id,
        ];
    }
}
