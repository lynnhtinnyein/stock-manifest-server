<?php

namespace Database\Seeders\Versions\v1;

use Database\Seeders\VersionedSeeder;

class Index extends VersionedSeeder
{
    public function version(): int
    {
        return 1;
    }

    public function runSeeds(): void
    {
        $this->call([
            PermissionSeeder::class,
            UserSeeder::class,
            SettingSeeder::class,
            CategorySeeder::class,
            ItemSeeder::class,
        ]);
    }
}
