<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

//update password is different from change password
//update password used by admin to change other user's password
//change password used by user to change their own password
class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!$this->user()) {
            return false;
        }

        return $this->user()->hasPermission("users.manage");
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'same:confirmPassword', Password::defaults()],
            'confirmPassword' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Please provide a new password.',
            'password.same' => 'The password and confirmation do not match.',
            'confirmPassword.required' => 'Please confirm your password.',
        ];
    }
}
