<?php

namespace App\Models;

use App\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;

    protected $fillable = [
        'name',
        'nickname',
        'username',
        'password',
        'role',
        'is_suspended',
        'suspended_at',
        'suspended_by',
        'restored_at',
        'restored_by',
        'created_by'
    ];

    protected $hidden = ['username', 'password', 'remember_token'];

    protected $casts = [
        'is_suspended' => 'boolean',
        'suspended_at' => 'datetime',
        'restored_at' => 'datetime',
    ];

    // Relations
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    // Suspend helpers
    public function isSuspended(): bool
    {
        return (bool) $this->is_suspended;
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function restoredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public function isActive(): bool
    {
        return !$this->is_suspended;
    }

    // Permissions
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuspended()) {
            return false;
        }

        return $this->permissions()->where('value', $permission)->exists();
    }
}
