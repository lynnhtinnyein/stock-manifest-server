<?php

namespace Database\Seeders\Versions\v1;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissionIds = Permission::pluck('id')->toArray();

        //create super-admin
        $superAdmin = User::firstOrCreate(
            [
                'username' => 'yoonyoon',
                'name' => 'Yoon Yoon',
                'nickname' => 'Yoon Yoon',
                'password' => Hash::make('51017'),
                'role' => 'SUPER_ADMIN',
            ]
        );
        $superAdmin->update(['created_by' => $superAdmin->id]);
        $superAdmin->permissions()->sync($allPermissionIds);
    }
}
