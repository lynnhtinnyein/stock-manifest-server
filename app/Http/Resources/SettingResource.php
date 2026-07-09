<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class SettingResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exchangeRate' => $this->exchange_rate,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
