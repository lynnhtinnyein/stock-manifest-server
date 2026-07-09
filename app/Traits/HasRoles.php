<?php

namespace App\Traits;

use App\Services\RoleService;

trait HasRoles
{
    private function roles(): RoleService
    {
        return app(RoleService::class);
    }

    public function getRoleLevel(): int
    {
        return $this->roles()->getLevel($this->role);
    }

    public function canManage($target): bool
    {
        if (!is_string($target) && $this->id === $target->id) return false;

        $targetRole = is_string($target) ? $target : $target->role;
        return $this->roles()->canManage($this->role, $targetRole);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'SUPER_ADMIN';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'ADMIN';
    }

    public function isUser(): bool
    {
        return $this->role === 'USER';
    }

    public function getManageableRoles(): array
    {
        return $this->roles()->getManageableRoles($this->role);
    }
}
