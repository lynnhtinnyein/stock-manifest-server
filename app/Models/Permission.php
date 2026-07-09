<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'description',
        'value',
        'is_hidden',
        'risk_level',
        'is_admin_default',
        'accessible_roles'
    ];

    protected $casts = [
        'is_admin_default' => 'boolean',
        'is_hidden' => 'boolean',
        'risk_level' => 'integer',
        'accessible_roles' => 'array',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withTimestamps();
    }

    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'permission_dependencies',
            'permission_id',
            'dependency_id'
        );
    }
}
