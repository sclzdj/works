<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class PhotographerGatherInfo extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'photographer_id',
        'photographer_rank_id',
        'start_year',
        'is_default',
        'status',
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
            'photographer_id',
            'photographer_rank_id',
            'start_year',
            'is_default',
            'created_at',
        ];
    }
}
