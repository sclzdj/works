<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class PhotographerWorkSource extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'photographer_work_id',
        'url',
        'init_size',
        'deal_url',
        'deal_size',
        'type',
        'origin',
        'sort',
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
            'photographer_work_id',
            'url',
            'deal_url',
            'type',
            'origin',
            'created_at',
        ];
    }
}
