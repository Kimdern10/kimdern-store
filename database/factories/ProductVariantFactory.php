<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'size' => fake()->randomElement(['S', 'M', 'L', 'XL']),
            'colour' => fake()->randomElement(['Black', 'White', 'Red']),
            'colour_hex' => fake()->hexColor(),
            'price' => null,
            'stock' => 10,
        ];
    }
}
