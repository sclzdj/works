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

    public static function createInvote($type = 2, $orderId = 0, $use_count = 1)
    {
        $invoteCode = new self();
        $invoteCode->code = substr(self::str_Rand(6), 0, 6);
        $invoteCode->type = $type;
        $invoteCode->status = 0;
        $invoteCode->user_id = 0;
        $invoteCode->order_id = $orderId;
        $invoteCode->order_id = $orderId;
        $invoteCode->used_count = $use_count;
        $invoteCode->created_at = date('Y-m-d H:i:s');
        $invoteCode->save();
        return $invoteCode->id;
    }

    private static function str_Rand($length)
    {
        $strs = "QWERTYUPASDFGHJKXCVBNM3456789";

        return substr(str_shuffle($strs), mt_rand(0, strlen($strs) - 11), $length);
    }

}
