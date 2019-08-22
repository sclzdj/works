<?php

namespace App\Model\Index;

use Illuminate\Notifications\Notifiable;#必须引用
use Illuminate\Foundation\Auth\User as Authenticatable;#必须引用
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
        'avatar',
        'gender',
        'country',
        'province',
        'city',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public static function allowFields() {
        return [
            'username',
            'nickname',
            'avatar',
            'gender',
            'country',
            'province',
            'city',
        ];
    }
}
