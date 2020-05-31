<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class Invite extends Model
{
    public $table = "invite";
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
