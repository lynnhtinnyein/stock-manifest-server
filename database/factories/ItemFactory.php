<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Item;
use App\Models\Quality;
use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        $category = Category::inRandomOrder()->first();
        $type = Type::find($category->type_id);
        $quality = Quality::inRandomOrder()->first();

        $orgWeight = fake()->numberBetween(10, 99);
        $serviceWeight = fake()->numberBetween(1, (int) ($orgWeight * 0.4));

        $hasGem = $category->is_gem_includable
            ? fake()->boolean(30)
            : false;

        $gemWeight = $hasGem
            ? fake()->numberBetween(1, max(1, $orgWeight - $serviceWeight - 1))
            : 0;

        $gemPrice = $hasGem
            ? fake()->numberBetween(50000, 500000)
            : 0;

        $netWeight = $orgWeight - $gemWeight;

        $price = $quality->is_fixed_price
            ? 1234567
            : fake()->numberBetween(100000, 5000000);

        $hasAttachment = $category->has_attachment;

        $attachmentOrgWeight = $hasAttachment
            ? fake()->numberBetween(10, 99)
            : 0;

        $attachmentServiceWeight = $hasAttachment
            ? fake()->numberBetween(1, (int) ($attachmentOrgWeight * 0.4))
            : 0;

        $attachmentHasGem = $hasAttachment && fake()->boolean(30);

        $attachmentGemWeight = $attachmentHasGem
            ? fake()->numberBetween(1, max(1, $attachmentOrgWeight - $attachmentServiceWeight - 1))
            : 0;

        $attachmentNetWeight = $hasAttachment
            ? $attachmentOrgWeight - $attachmentGemWeight
            : 0;

        $attachmentPrice = $hasAttachment
            ? fake()->numberBetween(50000, 500000)
            : 0;

        $attachmentGemPrice = $attachmentHasGem
            ? fake()->numberBetween(50000, 500000)
            : 0;

        return [
            'serial' => fake()->unique()->regexify($type->prefix . '[0-9]{5}'),

            'name' => $category->name,

            'size' => $category->has_size
                ? fake()->randomFloat(2, 1, 25)
                : 0,

            'type_id' => $type->id,
            'category_id' => $category->id,
            'quality_id' => $quality->id,

            'price' => $price,

            'org_weight' => $orgWeight,
            'service_weight' => $serviceWeight,
            'net_weight' => $netWeight,

            'has_gem' => $hasGem,
            'gem_weight' => $gemWeight,
            'gem_price' => $gemPrice,

            'attachment_price' => $attachmentPrice,
            'attachment_org_weight' => $attachmentOrgWeight,
            'attachment_service_weight' => $attachmentServiceWeight,
            'attachment_net_weight' => $attachmentNetWeight,

            'attachment_has_gem' => $attachmentHasGem,
            'attachment_gem_weight' => $attachmentGemWeight,
            'attachment_gem_price' => $attachmentGemPrice,

            'trader_weight' => fake()->numberBetween(0, $orgWeight),

            'created_by' => 1,
            'updated_by' => null,
        ];
    }
}
