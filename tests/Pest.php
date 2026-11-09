<?php

use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * A product with no sizes or colours. Prices are in kobo.
 *
 * @param  array<string, mixed>  $attributes
 */
function simpleProduct(array $attributes = []): Product
{
    return Product::factory()->for(Category::factory())->create($attributes + ['price' => 1000000, 'stock' => 10]);
}

/**
 * A product sold in sizes, with one variant per size.
 *
 * @param  array<string, int>  $stockBySize
 */
function sizedProduct(array $stockBySize = ['M' => 5, 'L' => 0], int $price = 2000000): Product
{
    $product = Product::factory()->for(Category::factory())->create(['price' => $price, 'stock' => 0]);

    foreach ($stockBySize as $size => $stock) {
        ProductVariant::factory()->for($product)->create(['size' => $size, 'colour' => null, 'colour_hex' => null, 'stock' => $stock]);
    }

    return $product->load('variants');
}

function lagosZone(int $fee = 250000, ?int $freeOver = null): DeliveryZone
{
    return DeliveryZone::factory()->create(['name' => 'Lagos', 'states' => ['Lagos'], 'fee' => $fee, 'free_over' => $freeOver]);
}

/**
 * @return array<string, string>
 */
function checkoutDetails(array $overrides = []): array
{
    return $overrides + [
        'email' => 'ada@example.com',
        'name' => 'Ada Obi',
        'phone' => '08031234567',
        'address' => '12 Allen Avenue',
        'city' => 'Ikeja',
        'state' => 'Lagos',
    ];
}
