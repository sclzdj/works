<?php

namespace App\Model\Admin;

use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    public $timestamps = false;//关闭时间维护
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'title',
        'value',
        'type',
        'genre',
        'tips',
        'options',
        'required',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * 获取配置项值
     *
     * @param        $name
     * @param string $type
     *
     * @return null
     */
    public static function getVal($name, $type = 'basic')
    {
        $systemConfig =
            SystemConfig::where('type', $type)->where('name', $name)->first();

        return $systemConfig ?
            $systemConfig->value :
            null;
    }

    /**
     * 获取前台链接地址
     *
     * @return null|string
     */
    public static function indexUrl()
    {
        $basic_index_url = SystemConfig::getVal('basic_index_url','basic');;

        if (!$basic_index_url || strpos($basic_index_url, 'http://') !== false ||
            strpos($basic_index_url, 'https://') !== false
        ) {
            return $basic_index_url;
        } else {
            return action($basic_index_url);
        }
    }
}
