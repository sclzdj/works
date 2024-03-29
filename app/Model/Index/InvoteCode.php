<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class InvoteCode extends Model
{
    public $status = [
        '0' => '已生成',
        '1' => '已绑定',
        '2' => '已校验',
        '4' => '已创建'
    ];

    public $sendType = [
        0 => '未发送',
        1 => '已发送'
    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'type',
        'status',
        'user_id',
        'used_count',
        'order_id',
        'created_at'
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
            'user_id',
            'used_count',
            'order_id',
            'created_at'
        ];
    }

    public static function createInvote(
        $type = 2, $orderId = 0, $use_count = 1,
        $user_id = 0, $status = 0
    )
    {
        $invoteCode = new self();
        $invoteCode->code = substr(self::str_Rand(6), 0, 6);
        $invoteCode->type = $type;
        $invoteCode->user_id = $user_id;
        $invoteCode->order_id = $orderId;
        $invoteCode->used_count = $use_count;
        $invoteCode->created_at = date('Y-m-d H:i:s');
        $invoteCode->status = $status;

        $invoteCode->save();
        return $invoteCode->id;
    }

    public static function str_Rand($length)
    {
        $strs = "QWERTYUPASDFGHJKXCVBNM3456789";

        return substr(str_shuffle($strs), mt_rand(0, strlen($strs) - 11), $length);
    }

}
