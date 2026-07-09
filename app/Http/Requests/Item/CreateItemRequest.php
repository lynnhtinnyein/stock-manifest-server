<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;

class CreateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission("items.default") ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'categoryId' => ['required', 'integer', 'exists:categories,id'],

            'priceInThb' => ['required', 'integer', 'min:0'],
            'weightInGrams' => ['nullable', 'integer', 'min:0'],
            'cargoPriceInMmk' => ['nullable', 'integer', 'min:0'],
            'sellingPriceInMmk' => ['nullable', 'integer', 'min:0'],

            'stockIn' => ['nullable', 'integer', 'min:0'],
            'stockOut' => ['nullable', 'integer', 'min:0'],

            'remark' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Item name is required.',
            'name.string' => 'Item name must be a valid text.',
            'name.max' => 'Item name cannot exceed 255 characters.',

            'categoryId.required' => 'Category is required.',
            'categoryId.integer' => 'Category ID must be a valid integer.',
            'categoryId.exists' => 'Selected category does not exist.',

            'priceInThb.required' => 'Price is required.',
            'priceInThb.integer' => 'Price must be a valid integer.',
            'priceInThb.min' => 'Price cannot be negative.',

            'weightInGrams.required' => 'Weight is required.',
            'weightInGrams.integer' => 'Weight must be a valid integer.',
            'weightInGrams.min' => 'Weight cannot be negative.',

            'cargoPriceInMmk.integer' => 'Cargo price must be a valid integer.',
            'cargoPriceInMmk.min' => 'Cargo price cannot be negative.',

            'sellingPriceInMmk.integer' => 'Selling price must be a valid integer.',
            'sellingPriceInMmk.min' => 'Selling price cannot be negative.',

            'stockIn.integer' => 'Stock in must be a valid integer.',
            'stockIn.min' => 'Stock in cannot be negative.',

            'stockOut.integer' => 'Stock out must be a valid integer.',
            'stockOut.min' => 'Stock out cannot be negative.',

            'remark.string' => 'Remark must be a valid text.',
        ];
    }
}
