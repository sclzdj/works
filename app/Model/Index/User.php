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
        'xacode',
        'share_url'
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
            'xacode',
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

    /**
     * 为用户生成小程序吗
     */
    public static function createXacode($id, $type = 'photographer')
    {
        if ($type == 'photographer_work') {
            $page =  'pages/productDetails/productDetails';
            $photographer_work = PhotographerWork::find($id);
            if (!$photographer_work) {
                return '';
            }
            $photographer = Photographer::find($photographer_work->photographer_id);
        } else {
            $page ='pages/homes/homes';
            $photographer = Photographer::find($id);
        }
        if (!$photographer) {
            return '';
        }
        $response = WechatServer::getxacodeunlimit($id, $page);
        if ($response['code'] == 200) {
            $filename = 'xacodes/'.time().mt_rand(10000, 99999).'.png';
            $xacode = Image::make($response['data'])->resize(370, 370);
            $bgimg = Image::make('xacodes/bg.png')->resize(420, 420);
            $bgimg->insert($xacode, 'top-left', 25, 25);
            $bgimg->save($filename);
            $bucket = 'zuopin';
            $buckets = config('custom.qiniu.buckets');
            $domain = $buckets[$bucket]['domain'] ?? '';
            //用于签名的公钥和私钥
            $accessKey = config('custom.qiniu.accessKey');
            $secretKey = config('custom.qiniu.secretKey');
            // 初始化签权对象
            $auth = new Auth($accessKey, $secretKey);
            // 生成上传Token
            $upToken = $auth->uploadToken($bucket);
            // 构建 UploadManager 对象
            $uploadMgr = new UploadManager();
            list($ret, $err) = $uploadMgr->putFile($upToken, null, $filename);
            @unlink($filename);
            if ($err) {
                return '';
            }
            if (!$photographer->avatar) {
                return $domain.'/'.$ret['key'].'?roundPic/radius/!50p';
            }
            $avatar = $photographer->avatar.'?imageMogr2/thumbnail/170x170!|roundPic/radius/!50p';
            $avatar_bg = config('app.url').'/xacodes/avatar_bg.png';

            return $domain.'/'.$ret['key'].'?watermark/3/image/'.\Qiniu\base64_urlSafeEncode(
                    $avatar_bg
                ).'/dx/125/dy/125/image/'.\Qiniu\base64_urlSafeEncode(
                    $avatar
                ).'/dx/125/dy/125|roundPic/radius/!50p';
        } else {
            return '';
        }
    }

    /**
     * 改成185 x 185大小的 小程序码
     */
    public static function createXacode2($id, $type = 'photographer')
    {
        if ($type == 'photographer_work') {
            $page =  'pages/productDetails/productDetails';
            $photographer_work = PhotographerWork::find($id);
            if (!$photographer_work) {
                return '';
            }
            $photographer = Photographer::find($photographer_work->photographer_id);
        } else {
            $page ='pages/homes/homes';
            $photographer = Photographer::find($id);
        }
        if (!$photographer) {
            return '';
        }
        $response = WechatServer::getxacodeunlimit($id, $page);
        if ($response['code'] == 200) {
            $filename = 'xacodes/'.time().mt_rand(10000, 99999).'.png';
            $xacode = Image::make($response['data'])->resize(420, 420);
//            $bgimg = Image::make('xacodes/bg.png')->resize(420, 420);
//            $xacode->insert($xacode, 'top-left', 25, 25);
            $xacode->save($filename);
            $bucket = 'zuopin';
            $buckets = config('custom.qiniu.buckets');
            $domain = $buckets[$bucket]['domain'] ?? '';
            //用于签名的公钥和私钥
            $accessKey = config('custom.qiniu.accessKey');
            $secretKey = config('custom.qiniu.secretKey');
            // 初始化签权对象
            $auth = new Auth($accessKey, $secretKey);
            // 生成上传Token
            $upToken = $auth->uploadToken($bucket);
            // 构建 UploadManager 对象
            $uploadMgr = new UploadManager();
            list($ret, $err) = $uploadMgr->putFile($upToken, null, $filename);
            @unlink($filename);
            if ($err) {
                return '';
            }
            if (!$photographer->avatar) {
                return $domain.'/'.$ret['key'].'?roundPic/radius/!50p';
            }
            $avatar = $photographer->avatar.'?imageMogr2/thumbnail/190x190!|roundPic/radius/!50p';

            $avatar_bg = config('app.url').'/xacodes/avatar_bg.png';

            return $domain.'/'.$ret['key'].'?watermark/3/image/'.\Qiniu\base64_urlSafeEncode(
                    $avatar
                ).'/dx/115/dy/115';
        } else {
            return '';
        }
    }
}
