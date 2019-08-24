<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class SmsCode extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'mobile',
        'code',
        'purpose',
        'ip',
        'is_used',
        'expired_at',
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
    public static function allowFields() {
        return [
            'id',
            'mobile',
            'code',
            'purpose',
            'ip',
            'is_used',
            'expired_at',
            'created_at',
        ];
    }
}
