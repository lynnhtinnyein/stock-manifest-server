<?php

namespace App\Services;

use Auth;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;

class UserService
{
    public function __construct(private RoleService $roles) {}

    private function relations(): array
    {
        return [
            'permissions',
            'suspendedBy:id,name,nickname',
            'restoredBy:id,name,nickname',
            'createdBy:id,name,nickname'
        ];
    }

    public function getAll(array $params): LengthAwarePaginator
    {
        $filters = $params['filters'] ?? [];
        $sorting = $params['sorting'] ?? [];
        $authUser = $params['authUser'] ?? null;
        $perPage = $params['perPage'] ?? 15;

        $query = ($filters['onlyTrashed'] ?? false) ? User::onlyTrashed() : User::query();

        // Exclude current user
        if ($authUser?->id) {
            $query->where('id', '!=', $authUser->id);
        }

        // Apply role-based filtering
        if ($authUser?->role !== 'SUPER_ADMIN') {
            $query->where('role', 'user');
        }

        $sortBy = $sorting['sortBy'] ?? 'created_at';
        $sortBy = $sortBy === 'status' ? 'is_suspended' : Str::snake($sortBy);
        $sortOrder = $sorting['sortOrder'] ?? 'DESC';

        $isSortByName = in_array($sortBy, ['name']);

        // Load relationships
        $query->with($this->relations());

        // Apply search filter
        if ($search = $filters['search'] ?? null) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('username', 'LIKE', "%{$search}%");
            });
        }

        // Apply status filter
        $status = $filters['status'] ?? null;
        if ($status && $status !== 'ALL') {
            $query->where('is_suspended', $status === 'SUSPENDED');
        }

        // Apply role filter
        $role = $filters['role'] ?? null;
        if ($role && $role !== 'ALL') {
            $query->where('role', $role);
        }

        // Apply sorting
        if ($sortBy === 'permissions') {
            $query->withCount('permissions')->orderBy('permissions_count', $sortOrder);
        } else {
            $query->orderBy($isSortByName ? 'name' : $sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    public function getById(int $id): ?User
    {
        return User::with($this->relations())->find($id);
    }

    public function create(array $data, array $permissionIds = []): User
    {
        $authUser = Auth::user() ?? throw new \Exception('Not authenticated');
        $role = $data['role'] ?? 'USER';

        $this->roles->validate($authUser->role, $role, 'store');

        // Hard guard: only one SUPER_ADMIN can exist at any time
        if ($role === 'SUPER_ADMIN' && User::where('role', 'SUPER_ADMIN')->exists()) {
            throw new \Exception('A Super Admin already exists.');
        }

        $user = User::create([
            'name'       => $data['name'],
            'nickname'   => $data['nickname'] ?? $data['name'],
            'username'   => $data['username'],
            'password'   => Hash::make($data['password']),
            'role'       => $role,
            'created_by' => $authUser->id,
        ]);

        $permissions = $this->roles->filterOutEligiblePermissionsOnly($role, $permissionIds);
        if ($permissions) $user->permissions()->sync($permissions);

        return $user->fresh();
    }

    public function update(User $user, array $data, array $permissionIds = []): User
    {
        $authUser = Auth::user() ?? throw new \Exception('Not authenticated');

        $this->roles->validate($authUser->role, $user->role, 'update');

        if (isset($data['role']) && $data['role'] !== $user->role) {
            $this->roles->validate($authUser->role, $data['role'], 'assign');
        }

        unset($data['permissionIds']);
        $user->update($data);

        $permissions = $this->roles->filterOutEligiblePermissionsOnly($user->role, $permissionIds);
        $user->permissions()->sync($permissions);

        return $user->fresh();
    }

    public function toggleAccountSuspension(User $user, int $actionById, bool $suspend): User
    {
        $authUser = Auth::user() ?? throw new \Exception('Not authenticated');

        $this->roles->validate($authUser->role, $user->role, 'suspend');

        $user->update([
            'is_suspended' => $suspend,
            'suspended_by' => $suspend ? $actionById : null,
            'suspended_at' => $suspend ? now() : null,
            'restored_by' => $suspend ? null : $actionById,
            'restored_at' => $suspend ? null : now(),
        ]);

        return $user->fresh();
    }

    public function delete(int $id): bool
    {
        $authUser = Auth::user() ?? throw new \Exception('Not authenticated');
        $user = User::findOrFail($id);

        $this->roles->validate($authUser->role, $user->role, 'delete');

        return $user->delete();
    }

    public function forceDelete(int $id): bool
    {
        $authUser = Auth::user() ?? throw new \Exception('Not authenticated');
        $user = User::onlyTrashed()->findOrFail($id);

        $this->roles->validate($authUser->role, $user->role, 'delete');

        return $user->forceDelete();
    }

    public function restoreDeleted(int $id): bool
    {
        $authUser = Auth::user() ?? throw new \Exception('Not authenticated');
        $user = User::onlyTrashed()->findOrFail($id);

        $this->roles->validate($authUser->role, $user->role, 'restore');

        return $user->restore();
    }
}
