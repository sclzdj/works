<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class OrderInfo extends Model
{
    public $table = "order_info";
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


    static public function getName(){
        return [

        ];
    }


}
