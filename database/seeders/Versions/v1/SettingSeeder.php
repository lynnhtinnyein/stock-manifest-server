<?php

namespace Database\Seeders\Versions\v1;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        Setting::create(
            [
                'exchange_rate' => 135,
            ]
        );
    }
}
