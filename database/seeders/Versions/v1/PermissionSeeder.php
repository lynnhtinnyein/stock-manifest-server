<?php

namespace Database\Seeders\Versions\v1;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data BEFORE transaction
        DB::table('permission_dependencies')->delete();
        DB::table('permission_user')->delete();
        DB::table('permissions')->delete();

        DB::transaction(function () {
            $permissions = [
                //items
                [
                    //done
                    'value' => 'items.default',
                    'name' => 'Default',
                    'description' => 'Default',
                    'is_hidden' => true,
                    'risk_level' => 1,
                    'is_admin_default' => true,
                    'accessible_roles' => ["SUPER_ADMIN", "ADMIN", "USER"]
                ],
            ];

            foreach ($permissions as $permission) {
                Permission::create($permission);
            }

            $dependencies = [];

            foreach ($dependencies as $permissionValue => $dependsOn) {
                $permission = Permission::where('value', $permissionValue)->first();

                if ($permission) {
                    foreach ($dependsOn as $dependsOnValue) {
                        $dependsOnPermission = Permission::where('value', $dependsOnValue)->first();

                        if ($dependsOnPermission) {
                            DB::table('permission_dependencies')->insert([
                                'permission_id' => $permission->id,
                                'dependency_id' => $dependsOnPermission->id,
                            ]);
                        }
                    }
                }
            }
        });
    }
}
