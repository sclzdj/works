<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class SendAliShortMessageLog extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'mobile',
        'template_code',
        'content_vars',
        'status',
        'third_response',
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
            'mobile',
            'template_code',
            'content_vars',
            'status',
            'third_response',
            'created_at',
        ];
    }
}
