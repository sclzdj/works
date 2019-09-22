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
        'size',
        'width',
        'height',
        'deal_key',
        'deal_url',
        'deal_size',
        'deal_width',
        'deal_height',
        'rich_key',
        'rich_url',
        'rich_size',
        'rich_width',
        'rich_height',
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
            'size',
            'width',
            'height',
            'deal_key',
            'deal_url',
            'deal_size',
            'deal_width',
            'deal_height',
            'rich_key',
            'rich_url',
            'rich_size',
            'rich_width',
            'rich_height',
            'type',
            'origin',
            'created_at',
        ];
    }
}
