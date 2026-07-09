<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseEntityResource extends JsonResource
{
    public function toArray(Request $request): array | null
    {
        if (!$this->resource) {
            return null;
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
