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
        'async_baidu_work_sources_upload_id',
        'dlink',
        'category',
        'size',
        'sort',
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
            'async_baidu_work_sources_upload_id',
            'dlink',
            'category',
            'size',
            'sort',
            'status',
            'created_at',
        ];
    }
}
