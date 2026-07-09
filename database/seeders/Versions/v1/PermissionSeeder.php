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
                // users
                [
                    'value' => 'users.view',
                    'name' => 'View Users',
                    'description' => 'View all user accounts and information',
                    'is_admin_default' => true,
                    'accessible_roles' => ["SUPER_ADMIN", "ADMIN"],
                ],
                [
                    'value' => 'users.create',
                    'name' => 'Create Accounts',
                    'description' => 'Create new user account to system with specific role and permissions',
                    'is_admin_default' => true,
                    'accessible_roles' => ["SUPER_ADMIN", "ADMIN"]
                ],
                [
                    'value' => 'users.update',
                    'name' => 'Edit Accounts',
                    'description' => 'Modify account details, including updating user information and resetting passwords',
                    'is_admin_default' => true,
                    'accessible_roles' => ["SUPER_ADMIN", "ADMIN"]
                ],
                [
                    'value' => 'users.suspend',
                    'name' => 'Suspend Accounts',
                    'description' => 'Suspend or restore user accounts',
                    'is_admin_default' => true,
                    'accessible_roles' => ["SUPER_ADMIN", "ADMIN"]
                ],
                [
                    'value' => 'users.delete',
                    'name' => 'Delete Accounts',
                    'description' => 'Remove user accounts from the system permanently',
                    'is_admin_default' => true,
                    'accessible_roles' => ["SUPER_ADMIN", "ADMIN"]
                ],

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

            $dependencies = [
                'users.create' => ['users.view'],
                'users.update' => ['users.view'],
                'users.delete' => ['users.view'],
                'users.updatePassword' => ['users.view'],
                'users.suspend' => ['users.view'],
            ];

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
