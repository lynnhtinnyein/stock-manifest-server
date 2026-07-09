<?php

namespace App\Http\Resources;

use App\Http\Resources\BaseResource;

class PermissionResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'isAdminDefault' => (bool) $this->is_admin_default,
            'accessibleRoles' => $this->accessible_roles,
            'value' => $this->value,
            'isHidden' => $this->is_hidden,
            'riskLevel' => $this->risk_level,
            'dependencyIds' => $this->dependencies->pluck('id')->toArray(),
        ];
    }
}
