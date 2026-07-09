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
            'exchangeRate' => ['sometimes', 'required', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'exchangeRate.required' => 'Exchange rate is required.',
            'exchangeRate.integer' => 'Exchange rate must be a valid integer.',
            'exchangeRate.min' => 'Exchange rate cannot be negative.',
        ];
    }
}
