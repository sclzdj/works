<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class PhotographerRankingLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'photographer_id',
        'ranking',
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
            'photographer_id',
            'ranking',
            'created_at',
        ];
    }
}
