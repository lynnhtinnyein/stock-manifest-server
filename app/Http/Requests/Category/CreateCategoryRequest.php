<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class CreateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('items.default') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.string' => 'Category name must be a valid text.',
            'name.max' => 'Category name cannot exceed 255 characters.',

            'color.string' => 'Color must be a valid text.',
            'color.max' => 'Color cannot exceed 255 characters.',
        ];
    }
}
