<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use App\Rules\PermissionDependencies;
use App\Services\RoleService;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission("users.manage") ?? false;
    }

    public function rules(): array
    {
        $roles = app(RoleService::class);

        return [
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['sometimes', 'nullable', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'same:confirmPassword', Password::defaults()],
            'confirmPassword' => ['required', 'string'],
            'role' => [
                'required',
                Rule::in("SUPER_ADMIN", "ADMIN", "USER") //new SUPER_ADMIN cannot be created.
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
            'name.required' => 'Name is required.',
            'nickname.string' => 'Nickname must be a valid string.',
            'nickname.max' => 'Nickname cannot exceed 255 characters.',
            'username.required' => 'Username is required.',
            'username.unique' => 'This username is already registered.',
            'password.required' => 'Password is required.',
            'password.same' => 'Password and confirmation do not match.',
            'role.required' => 'Role is required.',
            'role.in' => 'Invalid user role.',
            'permissionIds.array' => 'Permissions must be provided as an array.',
            'permissionIds.*.integer' => 'Each permission ID must be a valid integer.',
            'permissionIds.*.exists' => 'One or more selected permissions do not exist.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Set default role if not provided
        if (!$this->has('role')) {
            $this->merge(['role' => 'USER']);
        }
    }
}
