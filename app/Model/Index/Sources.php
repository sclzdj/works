<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class Sources extends Model
{
    protected $table = "target_users";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

}
