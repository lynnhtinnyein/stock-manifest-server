<?php

namespace App\Http\Resources;

use App\Http\Resources\BaseResource;
use App\Http\Resources\MiniUserResource;
use Illuminate\Http\Request;

class ItemResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => new CategoryResource($this->category),
            'priceInThb' => $this->price_in_thb,
            'weightInGrams' => $this->weight_in_grams,
            'cargoPriceInMmk' => $this->cargo_price_in_mmk,
            'sellingPriceInMmk' => $this->selling_price_in_mmk,
            'stockIn' => $this->stock_in,
            'stockOut' => $this->stock_out,
            'remark' => $this->remark,
            'createdAt' => $this->created_at,
            'createdBy' => new MiniUserResource($this->createdBy),
            'updatedAt' => $this->updated_at,
            'updatedBy' => new MiniUserResource($this->updatedBy),
            'image' => $this->whenLoaded(
                'image',
                fn() => $this->image
                    ? new AttachmentResource($this->image)
                    : null
            ),
        ];
    }
}
