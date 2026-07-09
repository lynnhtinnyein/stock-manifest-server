<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Permission;

class PermissionDependencies implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value) || empty($value)) {
            return;
        }

        $permissions = Permission::with('dependencies')
            ->whereIn('id', $value)
            ->get();

        $selectedIds = collect($value);
        $missingDependencies = [];

        foreach ($permissions as $permission) {
            $dependencyIds = $permission->dependencies->pluck('id');

            $missingDeps = $dependencyIds->diff($selectedIds);

            if ($missingDeps->isNotEmpty()) {
                $missingDepNames = Permission::whereIn('id', $missingDeps)
                    ->pluck('name')
                    ->toArray();

                $missingDependencies[$permission->name] = $missingDepNames;
            }
        }

        if (!empty($missingDependencies)) {
            $messages = [];
            foreach ($missingDependencies as $permissionName => $dependencies) {
                $messages[] = "{$permissionName} requires " . implode(', ', $dependencies);
            }

            $fail('Permission dependencies not met. ' . implode('. ', $messages));
        }
    }
}
