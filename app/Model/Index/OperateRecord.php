<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class OperateRecord extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'photographer_id',
        'photographer_work_id',
        'photographer_gather_id',
        'page_name',
        'operate_type',
        'in_type',
        'share_type',
        'shared_user_id',
        'is_read',
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
            'id',
            'user_id',
            'photographer_id',
            'photographer_work_id',
            'photographer_gather_id',
            'page_name',
            'operate_type',
            'in_type',
            'share_type',
            'shared_user_id',
            'is_read',
            'created_at',
        ];
    }
}
