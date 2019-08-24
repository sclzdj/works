<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class Photographer extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'avatar',
        'bg_img',
        'province',
        'city',
        'area',
        'rank',
        'wechat',
        'mobile',
        'status',
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
    public static function allowFields() {
        return [
            'id',
            'name',
            'avatar',
            'bg_img',
            'province',
            'city',
            'area',
            'rank',
            'wechat',
            'mobile',
            'created_at',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function photographerWorks()
    {
        return $this->hasMany(PhotographerWork::class);
    }
}
