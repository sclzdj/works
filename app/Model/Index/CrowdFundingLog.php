<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class CrowdFundingLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

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
            'open_id',
            'phone',
            'crowd_status',
            'crowd_time',
            'type'
        ];
    }

}
