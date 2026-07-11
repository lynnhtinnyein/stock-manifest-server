<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('items.default') ?? false;
    }

    public function rules(): array
    {
        return [
            'exchangeRate' => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'exchangeRate.required' => 'Exchange rate is required.',
            'exchangeRate.numeric' => 'Exchange rate must be a valid number.',
            'exchangeRate.min' => 'Exchange rate cannot be negative.',
        ];
    }
}
