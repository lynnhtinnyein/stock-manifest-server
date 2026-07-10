<?php

namespace Database\Seeders\Versions\v1;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        Item::factory()->count(20)->create();
    }
}
