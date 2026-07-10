<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        $priceInThb = $this->faker->numberBetween(10, 1000);
        $cargoPrice = $this->faker->numberBetween(500, 30000);

        $stockIn = $this->faker->numberBetween(10, 300);
        $stockOut = $this->faker->numberBetween(0, $stockIn);

        return [
            'name' => ucfirst($this->faker->unique()->words(rand(2, 4), true)),
            'category_id' => Category::factory(),
            'price_in_thb' => $priceInThb,
            'weight_in_grams' => $this->faker->numberBetween(1, 5000),
            'cargo_price_in_mmk' => $cargoPrice,
            'selling_price_in_mmk' => $cargoPrice + $this->faker->numberBetween(1000, 100000),
            'stock_in' => $stockIn,
            'stock_out' => $stockOut,
            'remark' => $this->faker->optional()->sentence(),
            'created_by' => User::factory(),
            'updated_by' => User::factory(),
        ];
    }
}
