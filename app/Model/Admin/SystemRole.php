<?php

namespace App\Model\Admin;

use Illuminate\Database\Eloquent\Model;

class SystemRole extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'status',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function systemNodes()
    {
        return $this->belongsToMany(SystemNode::class, 'system_role_nodes',
                                    'system_role_id', 'system_node_id');
    }
}
