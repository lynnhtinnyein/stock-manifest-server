<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->unique()->words(rand(1, 3), true)),
            'color' => $this->faker->hexColor(),
        ];
    }
}
