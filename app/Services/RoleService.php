<?php

namespace App\Services;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Facades\Cache;

class RoleService
{
    // Role levels: higher number = more power
    private const LEVELS = [
        'SUPER_ADMIN' => 3,
        'ADMIN' => 2,
        'USER' => 1
    ];

    public function getLevel(string $role): int
    {
        return self::LEVELS[$role] ?? 0;
    }

    public function canManage(string $authRole, string $targetRole): bool
    {
        return $this->getLevel($authRole) > $this->getLevel($targetRole);
    }

    public function validate(string $authRole, string $targetRole, string $action = 'access'): void
    {
        // super-admin can create another super-admin
        if ($action === 'store' && $authRole === 'SUPER_ADMIN' && $targetRole === 'SUPER_ADMIN') {
            return;
        }

        if (!$this->canManage($authRole, $targetRole)) {
            throw new \Exception("You don't have enough permission to do this action.");
        }
    }

    public function canAccess(User $authUser, User $targetUser): bool
    {
        // Users can always access their own account
        if ($authUser->id === $targetUser->id) return true;

        // No one can access super-admin accounts except themselves
        if ($targetUser->role === 'SUPER_ADMIN') return false;

        return $this->canManage($authUser->role, $targetUser->role);
    }

    private function getAdminDefaultPermissionIds(): array
    {
        return Cache::remember(
            'admin_only_permissions',
            3600,
            fn() => Permission::where('is_admin_default', true)->whereJsonContains('accessible_roles', 'ADMIN')->pluck('id')->toArray()
        );
    }

    public function filterOutEligiblePermissionsOnly(string $role, array $permissionIds = []): array
    {
        if (empty($permissionIds)) {
            return $role === 'ADMIN' ? $this->getAdminDefaultPermissionIds() : [];
        }

        $permissions = Permission::whereIn('id', $permissionIds)->get();

        $eligiblePermissionIds = $permissions
            ->filter(function ($permission) use ($role) {
                return empty($permission->accessible_roles)
                    || in_array($role, $permission->accessible_roles);
            })
            ->pluck('id')
            ->toArray();

        if ($role === 'ADMIN') {
            $adminDefaults = $this->getAdminDefaultPermissionIds();
            $eligiblePermissionIds = array_merge($eligiblePermissionIds, $adminDefaults);
        }

        return array_values(array_unique($eligiblePermissionIds));
    }

    public function getValidRoles(): array
    {
        return array_keys(self::LEVELS);
    }

    public function getManageableRoles(string $role): array
    {
        $level = $this->getLevel($role);
        return array_keys(array_filter(self::LEVELS, fn($l) => $l < $level));
    }
}
