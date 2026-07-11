<?php

namespace Database\Seeders\Versions\v1;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::create([
            'name' => 'Snacks',
            'color' => '#f43f5e',
        ]);
    }
}
