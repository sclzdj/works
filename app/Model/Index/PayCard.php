<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class PayCard extends Model
{
    public $table = "pay_card";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'invite_id',
        'remark'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];




}
