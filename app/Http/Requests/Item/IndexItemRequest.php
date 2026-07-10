<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (!$this->user()) {
            return false;
        }

        return $this->user()->hasPermission("items.default");
    }

    public function rules(): array
    {
        return [
            'perPage' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
            'sortBy' => [
                'sometimes',
                'string',
                Rule::in([
                    'id',
                    'price',
                    'category',
                    'weight',
                    'sellingPrice',
                    'cargoPrice',
                    'stockStatus',
                    'createdAt',
                ]),
            ],
            'sortOrder' => [
                'sometimes',
                'string',
                Rule::in(['asc', 'ASC', 'desc', 'DESC']),
            ],
            'name' => 'sometimes|string|max:255|nullable',
            'categoryId' => 'sometimes|string|exists:categories,id|nullable',
        ];
    }

    public function messages(): array
    {
        return [
            'sortBy.in' => 'The sortBy field must be one of: id, price, category, weight, sellingPrice, cargoPriceThb, cargoPrice, stockStatus, or createdAt.',
            'sortOrder.in' => 'The sortOrder field must be either ASC or DESC.',
        ];
    }
}
