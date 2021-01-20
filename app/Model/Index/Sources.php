<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class Sources extends Model
{
    protected $table = "sources";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

}
