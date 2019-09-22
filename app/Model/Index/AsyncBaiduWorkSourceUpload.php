<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class AsyncBaiduWorkSourceUpload extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'photographer_work_source_id',
        'fs_id',
        'category',
        'size',
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
            'photographer_work_source_id',
            'fs_id',
            'category',
            'size',
            'status',
            'created_at',
        ];
    }
}
