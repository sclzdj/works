<?php

namespace App\Model\Index;


use Illuminate\Database\Eloquent\Model;


class Templates extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'number',
        'purpose',
        'text1',
        'text2',
        'text3',
        'text4',
        'background',
        'created_at',
        'updated_at'
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
            'number',
            'purpose',
            'text1',
            'text2',
            'text3',
            'text4',
            'background',
            'created_at',
        ];
    }


}
