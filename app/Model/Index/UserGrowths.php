<?php

namespace App\Model\Index;

use App\Servers\SystemServer;
use App\Servers\WechatServer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;#必须引用
use Illuminate\Foundation\Auth\User as Authenticatable;#必须引用
use Intervention\Image\Facades\Image;
use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Tymon\JWTAuth\Contracts\JWTSubject;#必须引用

class UserGrowths extends Model
{
    protected $table = 'user_growths';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
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

        ];
    }

    /**
     * 获取上次的登录时间
     * @return string time
     */
    public static function getLastLoginTime($user)
    {
        $loginInfo = self::where('user_id', $user->id)->first();
        if (empty($loginInfo)) {
            $updateTime = date('Y-m-d H:i:s');
            $loginNewInfo = new self();
            $loginNewInfo->user_id = $user->id;
            $loginNewInfo->last_login_at = $updateTime;
            $loginNewInfo->created_at = $updateTime;
            $loginNewInfo->save();
            return $updateTime;
        } else {
            self::where(['user_id' => $user->id])->update([
                'last_login_at' => date('Y-m-d H:i:s'),
            ]);
            return $loginInfo->last_login_at;
        }
    }

    /**
     * 获取上次登录时间增长了多少访客
     * @return int count
     */
    public static function getUserGrowthCount($user,$photographer)
    {
        $last_login_time = self::getLastLoginTime($user);
        $count = Visitor::where('photographer_id', $photographer->id)
            ->whereBetween('created_at', [$last_login_time, date('Y-m-d H:i:s')])
        ->count();

        return $count;
    }
}
