<?php

namespace App\Model\Admin;

use Illuminate\Database\Eloquent\Model;

class SystemDemo extends Model
{
    public $timestamps = false;//关闭时间维护
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
      'name',
      'value',
    ];
}
