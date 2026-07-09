<?php

namespace App\Http\Requests\Auth;

use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'currentPassword' => [
                'required',
                'string',
                'current_password'
            ],
            'newPassword' => [
                'required',
                'string',
                'same:confirmPassword',
                'different:currentPassword',
                Password::defaults()
            ],
            'confirmPassword' => [
                'required',
                'string'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'currentPassword.required' => 'Please provide your current password.',
            'currentPassword.current_password' => 'The current password you entered is incorrect.',
            'newPassword.required' => 'Please provide a new password.',
            'newPassword.same' => 'The new password and confirmation do not match.',
            'newPassword.different' => 'The new password must be different from your current password.',
            'confirmPassword.required' => 'Please confirm your new password.',
        ];
    }

    public function attributes(): array
    {
        return [
            'currentPassword' => 'current password',
            'newPassword' => 'new password',
            'confirmPassword' => 'password confirmation',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currentPassword' => trim($this->currentPassword ?? ''),
            'newPassword' => trim($this->newPassword ?? ''),
            'confirmPassword' => trim($this->confirmPassword ?? ''),
        ]);
    }
}
