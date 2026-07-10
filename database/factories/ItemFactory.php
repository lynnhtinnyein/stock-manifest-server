<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        $exchangeRate = $this->faker->numberBetween(120, 150);

        $priceInThb = $this->faker->randomFloat(2, 10, 1000);

        $cargoPriceInThb = $this->faker->randomFloat(2, 5, 300);
        $cargoPriceInMmk = round($cargoPriceInThb * $exchangeRate, 2);

        $sellingPriceInMmk = round(
            ($priceInThb * $exchangeRate)
                + $cargoPriceInMmk
                + $this->faker->randomFloat(2, 500, 10000),
            2
        );

        $stockIn = $this->faker->numberBetween(10, 300);
        $stockOut = $this->faker->numberBetween(0, $stockIn);

        return [
            'name' => ucfirst($this->faker->unique()->words(rand(2, 4), true)),

            'category_id' => Category::query()
                ->inRandomOrder()
                ->value('id'),

            'price_in_thb' => $priceInThb,
            'weight_in_grams' => $this->faker->numberBetween(1, 5000),

            'cargo_price_in_thb' => $cargoPriceInThb,
            'cargo_price_in_mmk' => $cargoPriceInMmk,

            'selling_price_in_mmk' => $sellingPriceInMmk,

            'stock_in' => $stockIn,
            'stock_out' => $stockOut,

            'remark' => $this->faker->optional()->sentence(),

            'created_by' => User::query()
                ->inRandomOrder()
                ->value('id'),

            'updated_by' => User::query()
                ->inRandomOrder()
                ->value('id'),
        ];
    }
}
