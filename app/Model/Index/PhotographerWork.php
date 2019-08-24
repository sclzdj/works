<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class PhotographerWork extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'customer_name',
        'customer_industry',
        'project_amount',
        'hide_project_amount',
        'sheets_number',
        'hide_sheets_number',
        'shooting_duration',
        'hide_shooting_duration',
        'category',
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
            'customer_name',
            'customer_industry',
            'project_amount',
            'hide_project_amount',
            'sheets_number',
            'hide_sheets_number',
            'shooting_duration',
            'hide_shooting_duration',
            'category',
            'created_at',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function photographerWorkImgs()
    {
        return $this->hasMany(PhotographerWorkImg::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function photographerWorkTags()
    {
        return $this->hasMany(PhotographerWorkTag::class);
    }
}
