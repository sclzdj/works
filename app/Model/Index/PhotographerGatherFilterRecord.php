<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class PhotographerGatherFilterRecord extends Model
{
    protected $table = 'photographer_gathers_filter_record';

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

    }

}
