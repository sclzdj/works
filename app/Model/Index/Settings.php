<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    public $table = "settings";
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
