<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $isSuspended = (bool) $this->is_suspended;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'isSuspended' => $isSuspended,
            'role' => $this->role,
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),

            // suspend
            'suspendedAt' => $isSuspended && $this->suspended_at
                ? $this->suspended_at
                : null,

            'suspendedBy' => new MiniUserResource($this->suspendedBy),

            //restore
            'restoredAt' => !$isSuspended && $this->restored_at
                ? $this->restored_at
                : null,

            'restoredBy' => new MiniUserResource($this->restoredBy),

            //create
            'createdAt' => $this->created_at,
            'createdBy' => new MiniUserResource($this->createdBy),

            'updatedAt' => $this->updated_at,
        ];
    }
}
