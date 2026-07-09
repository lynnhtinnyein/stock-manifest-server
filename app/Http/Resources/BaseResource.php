<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseResource extends JsonResource
{
    public static function collection($resource)
    {
        if ($resource instanceof LengthAwarePaginator) {
            return [
                'content' => parent::collection($resource->items()),
                'page' => $resource->currentPage(),
                'perPage' => $resource->perPage(),
                'totalPages' => $resource->lastPage(),
                'totalElements' => $resource->total(),
                'hasMore' => $resource->hasMorePages(),
            ];
        }

        return parent::collection($resource);
    }

    protected function asDate($value): ?string
    {
        if (!$value) return '';

        if (is_string($value)) {
            return Carbon::parse($value)->format('Y-m-d');
        }

        return $value->format('Y-m-d');
    }
}
