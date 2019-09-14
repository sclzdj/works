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
        'key',
        'url',
        'init_size',
        'deal_key',
        'deal_url',
        'deal_size',
        'rich_key',
        'rich_url',
        'rich_size',
        'type',
        'origin',
        'status',
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
            'key',
            'url',
            'init_size',
            'deal_key',
            'deal_url',
            'deal_size',
            'rich_key',
            'rich_url',
            'rich_size',
            'type',
            'origin',
            'created_at',
        ];
    }
}
