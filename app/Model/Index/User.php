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
        'password',
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
            'is_invite',
            'status',
            'photographer_id',
            'openid',
            'gh_openid',
            'created_at',
        ];
    }

    /**
     * 关联的用户
     * @param null $photographer_id
     * @return mixed
     */
    public static function photographer($photographer_id = null, $guard = null)
    {
        if (empty($photographer_id)) {
            if(auth($guard)->check()){
                $photographer_id = auth($guard)->user()->photographer_id;
            }else{
                return false;
            }
        }

        return Photographer::find($photographer_id);
    }

    /**
     * 创建微信用户预设一些东西
     */
    public static function presetCreate()
    {
        //先预设一个用户
        $photographer = Photographer::create();
        //再预设一个项目
        $photographer_work = PhotographerWork::create();
        $photographer_work->photographer_id = $photographer->id;
        $photographer_work->save();

        $photographerGather = PhotographerGather::create();
        $scene = '2/'. $photographer->id . '/' . $photographerGather->id;
        if (!$photographerGather->xacode) {
            $xacode_res = WechatServer::generateXacode($scene, false);
            if ($xacode_res['code'] == 200) {
                $photographerGather->xacode = $xacode_res['xacode'];
            }

        }
        if (!$photographerGather->xacode_hyaline) {
            $xacode_res = WechatServer::generateXacode($scene);
            if ($xacode_res['code'] == 200) {
                $photographerGather->xacode_hyaline = $xacode_res['xacode'];
            }
        }

        $photographerGather->photographer_id = $photographer->id;
        $photographerGather->name = '我的全部项目';
        $photographerGather->status = 200;
        $photographerGather->type = 3;
        $photographerGather->save();

        $photographerGatherWork = PhotographerGatherWork::create();
        $photographerGatherWork->photographer_gather_id = $photographerGather->id;
        $photographerGatherWork->photographer_work_id = $photographer_work->id;
        $photographerGatherWork->sort = 1;
        $photographerGatherWork->save();
        $photographer->save();

        return [
            'photographer_id' => $photographer->id,
        ];
    }
}
