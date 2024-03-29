<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class CrowdFunding extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [

    ];
    // 缓存的key prefix
    protected static $key = "crowdfunding_";

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /*
     * 初始化缓存数据
     *
     * @param string  $key
     * @param string  $amount
     *
     * @return void
     */
    public static function initCache()
    {
        $data = self::where('id', 1)->first()->toArray();

        \Cache::forever(self::$key . "amount", $data['amount']);
        \Cache::forever(self::$key . "total", $data['total']);
        \Cache::forever(self::$key . "total_price", $data['total_price']);
        \Cache::forever(self::$key . "target", $data['target']);
        \Cache::forever(self::$key . "complete_rate", $data['complete_rate']);
        \Cache::forever(self::$key . "data_99", $data['data_99']);
        \Cache::forever(self::$key . "data_399", $data['data_399']);
        \Cache::forever(self::$key . "data_599", $data['data_599']);
        \Cache::forever(self::$key . "limit_99", $data['limit_99']);
        \Cache::forever(self::$key . "limit_399", $data['limit_399']);
        \Cache::forever(self::$key . "limit_599", $data['limit_599']);
    }
    /*
     * 获取缓存的值
     *
     * @param string  $key
     * @param string  $amount
     *
     * @return void
     */
    public static function getKeyValue($key)
    {
        if (\Cache::has(self::$key . $key)) {
            return \Cache::get(self::$key . $key);
        } else {
            return null;
        }
    }
    /*
     * 增加缓存的值
     *
     * @param string  $key
     * @param string  $amount
     *
     * @return void
     */
    public static function increValue($key, $amount)
    {
        \Cache::increment(self::$key . $key, $amount);
    }
    /*
     * 减少缓存的值
     *
     * @param string  $key
     * @param string  $amount
     *
     * @return void
     */
    public static function decreValue($key, $amount)
    {
        \Cache::decrement(self::$key . $key, $amount);
    }
    /*
     * 设置缓存的值
     *
     * @param string  $key
     * @param string  $amount
     *
     * @return void
     */
    public static function ResetValue($key, $value)
    {
        \Cache::forever(self::$key . $key, $value);
    }



    /**
     * 允许查询的字段
     * @return array
     */
    public static function allowFields()
    {
        return [
            'amount',
            'total',
            'total_price',
            'target',
            'complete_rate',
            'data_99',
            'data_399',
            'data_599',
            'start_date',
            'end_date',
            'send_date',
        ];
    }


    public  function  setStartDateAttribute($value)
    {
        $this->attributes['start_date'] = strtotime($value);
    }

    public  function  setEndDateAttribute($value)
    {
        $this->attributes['end_date'] = strtotime($value);
    }

    public  function  setSendDateAttribute($value)
    {
        $this->attributes['send_date'] = strtotime($value);
    }

    public function  getStartDateAttribute(){
        return date('Y-m-d H:i:s',$this->attributes['start_date']);
    }

    public  function  getEndDateAttribute(){
        return date('Y-m-d H:i:s',$this->attributes['end_date']);
    }

    public  function  getSendDateAttribute(){
        return date('Y-m-d H:i:s',$this->attributes['send_date']);
    }



}
