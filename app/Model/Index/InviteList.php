<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class InviteList extends Model
{
    public $table = "invite_list";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'parent_photographer_id',
        'photographer_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];




}
