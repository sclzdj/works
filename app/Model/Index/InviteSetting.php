<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class InviteSetting extends Model
{
    public $table = "invite_settings";
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
