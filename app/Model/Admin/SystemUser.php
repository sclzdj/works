<?php

namespace App\Model\Admin;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class SystemUser extends Authenticatable
{
    use Notifiable;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
        'password',
        'nickname',
        'avatar',
        'type',
        'status',
        'remember_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function systemRoles()
    {
        return $this->belongsToMany(SystemRole::class, 'system_user_roles',
                                    'system_user_id', 'system_role_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function systemNodes()
    {
        return $this->belongsToMany(SystemNode::class, 'system_user_nodes',
                                    'system_user_id', 'system_node_id');
    }
}
