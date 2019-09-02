<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class PhotographerWorkCategory extends Model
{
    public $timestamps = false;//关闭时间维护

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'pid',
        'level',
        'name',
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
            'pid',
            'level',
            'name',
        ];
    }

    /*
    * 查出所有长辈，顺序从父级到根级
    * 切记此处返回数据千万不要用静态变量，会出现问题，具体不清楚
    */
    public static function elderCategories($id, $data = [])
    {
        $category = self::select(self::allowFields())->find($id);
        if ($category && $category->pid > 0 &&
            $pCategory = self::select(self::allowFields())->find($category->pid)
        ) {
            $data[] = $pCategory->toArray();

            return self::elderCategories($pCategory->id, $data);
        } else {
            return $data;
        }
    }
}
