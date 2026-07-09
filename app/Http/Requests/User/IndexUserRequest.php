<?php

namespace App\Http\Requests\User;

use App\Services\RoleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!$this->user()) {
            return false;
        }

        return $this->user()->hasPermission("users.view");
    }

    public function rules(): array
    {
        $roles = app(RoleService::class);

        return [
            'perPage' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|string|max:255|nullable',
            'page' => 'sometimes|integer|min:1',
            'sortBy' => [
                'sometimes',
                'string',
                Rule::in([
                    'id',
                    'name',
                    'permissions',
                    'role',
                    'status',
                    'createdAt',
                    'updatedAt',
                    'deletedAt',
                ]),
            ],
            'sortOrder' => [
                'sometimes',
                'string',
                Rule::in(['asc', 'ASC', 'desc', 'DESC']),
            ],
            'status' => [
                'sometimes',
                'string',
                'nullable',
                Rule::in(['ALL', 'ACTIVE', 'SUSPENDED']),
            ],
            'role' => [
                'sometimes',
                'string',
                'nullable',
                Rule::in(['ALL', ...$roles->getValidRoles()]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'sortBy.in' => 'The sortBy field must be one of: id, name, permissions, status, created_at, updated_at, deleted_at.',
            'sortOrder.in' => 'The sortOrder field must be either ASC or DESC.',
            'status.in' => 'The status field must be one of: ALL, SUSPENDED, ACTIVE.',
        ];
    }
}
