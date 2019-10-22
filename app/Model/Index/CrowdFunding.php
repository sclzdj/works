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

    protected $key = "crowdfunding_";

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public function initCache()
    {
        $data = self::where('id', 1)->first()->toArray();

        \Cache::forever($this->key . "amount", $data['amount']);
        \Cache::forever($this->key . "total", $data['total']);
        \Cache::forever($this->key . "total_price", $data['total_price']);
        \Cache::forever($this->key . "target", $data['target']);
        \Cache::forever($this->key . "complete_rate", $data['complete_rate']);
        \Cache::forever($this->key . "data_99", $data['data_99']);
        \Cache::forever($this->key . "data_399", $data['data_399']);
        \Cache::forever($this->key . "data_599", $data['data_599']);
        \Cache::forever($this->key . "limit_99", $data['limit_99']);
        \Cache::forever($this->key . "limit_399", $data['limit_399']);
        \Cache::forever($this->key . "limit_599", $data['limit_599']);
    }

    public static function getKeyValue($key)
    {
        if (\Cache::has("crowdfunding_" . $key)) {
            return \Cache::get("crowdfunding_" . $key);
        } else {
            return null;
        }
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

}
