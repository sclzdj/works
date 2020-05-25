<?php

namespace App\Model\Index;

use Illuminate\Database\Eloquent\Model;

class TargetUser extends Model
{
    protected $table = "target_users";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'source',
        'status',
        'invote_code_id',
        'user_id',
        'wechat',
        'address',
        'phone_code',
        'works_info',
        'created_at',
        'updated_at'
    ];

}
