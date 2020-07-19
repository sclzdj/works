<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class PhotographerGatherWork extends Model
{
    public $timestamps = false;//关闭时间维护
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'photographer_gather_id',
        'photographer_work_id',
        'sort',
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
            'photographer_gather_id',
            'photographer_work_id',
            'sort',
        ];
    }
}
