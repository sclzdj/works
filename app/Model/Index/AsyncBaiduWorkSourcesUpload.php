<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class AsyncBaiduWorkSourcesUpload extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'photographer_work_id',
        'status',
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
            'user_id',
            'photographer_work_id',
            'status',
            'created_at',
        ];
    }
}
