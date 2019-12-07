<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class Bootstrap extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'home',
        'work',
        'user',
        'relation',
        'storage',
        'created_at'
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
        ];
    }
}
