<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\PermissionDependencies;
use App\Models\User;
use App\Services\RoleService;

class UpdateUserRequest extends FormRequest
{

    public function authorize(): bool
    {
        $authUser = $this->user();
        if (!$authUser?->hasPermission("users.manage")) return false;

        // If role is being changed, require additional permission
        $user = $this->route('user');
        if ($this->has('role') && $this->input('role') !== $user->role) {
            \Log::info("Role change requested from {$user->role} to {$this->input('role')} by user ID {$authUser->id}");
            return $authUser->hasPermission("users.manage_roles");
        }

        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');
        if ($userId instanceof User) {
            $userId = $userId->id;
        }

        $roles = app(RoleService::class);

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'nickname' => ['sometimes', 'nullable', 'string', 'max:255'],
            'username' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('users')->ignore($userId)
            ],
            'role' => [
                'sometimes',
                Rule::in($roles->getValidRoles()),
            ],
            'permissionIds' => [
                'sometimes',
                'array',
                new PermissionDependencies()
            ],
            'permissionIds.*' => ['integer', 'exists:permissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.string' => 'Name must be a valid string.',
            'name.max' => 'Name cannot exceed 255 characters.',
            'nickname.string' => 'Nickname must be a valid string.',
            'nickname.max' => 'Nickname cannot exceed 255 characters.',
            'username.string' => 'Username must be a valid string.',
            'username.max' => 'Username cannot exceed 255 characters.',
            'username.unique' => 'This username is already registered.',
            'role.in' => 'Invalid user role.',
            'permissionIds.array' => 'Permissions must be provided as an array.',
            'permissionIds.*.integer' => 'Each permission ID must be a valid integer.',
            'permissionIds.*.exists' => 'One or more selected permissions do not exist.',
        ];
    }
}
