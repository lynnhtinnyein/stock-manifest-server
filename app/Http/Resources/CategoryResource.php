<?php

namespace App\Http\Resources;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class CategoryResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'typeId' => $this->type_id,
            'suffix' => $this->suffix,
            'hasSize' => $this->has_size,
            'hasAttachment' => $this->has_attachment,
            'isGemIncludable' => $this->is_gem_includable,
            'traderWeightCalculation' => $this->trader_weight_calculation,
        ];
    }
}
