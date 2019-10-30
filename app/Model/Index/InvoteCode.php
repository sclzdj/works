<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class InvoteCode extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'type',
        'status',
        'wechat_openid',
        'mini_openid',
        'order_id',
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
            'code',
            'type',
            'status',
            'wechat_openid',
            'mini_openid',
            'order_id',
            'created_at',
        ];
    }

}