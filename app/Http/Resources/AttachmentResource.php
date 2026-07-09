<?php

namespace App\Http\Resources;

use App\Http\Resources\MiniUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $className = class_basename($this->attachable_type);
        $resourceName = Str::plural(Str::kebab($className));

        return [
            'id' => $this->id,
            'originalName' => $this->original_name,
            'storedName' => $this->stored_name,
            'mimeType' => $this->mime_type,
            'size' => $this->size,
            'sizeFormatted' => $this->formatBytes($this->size),
            'url' => route("{$resourceName}.attachments.show", [
                'id' => $this->id
            ]),
            'downloadUrl' => route("{$resourceName}.attachments.download", [
                'id' => $this->id
            ]),
            'uploadedBy' => new MiniUserResource($this->uploadedBy),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }

    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
