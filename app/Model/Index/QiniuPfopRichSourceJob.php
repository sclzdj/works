<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class QiniuPfopRichSourceJob extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'photographer_work_source_id',
        'edit_node',
        'edit_at',
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
    public static function allowFields() {
        return [
            'photographer_work_source_id',
            'edit_node',
            'edit_at',
            'status',
            'created_at'
        ];
    }
}
