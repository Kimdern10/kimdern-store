<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'number' => 'KDS-'.Str::upper(Str::random(7)),
            'email' => fake()->safeEmail(),
            'name' => fake()->name(),
            'phone' => '08031234567',
            'address' => fake()->streetAddress(),
            'city' => 'Ikeja',
            'state' => 'Lagos',
            'subtotal' => 1000000,
            'discount' => 0,
            'delivery_fee' => 250000,
            'total' => 1250000,
            'status' => OrderStatus::Pending,
            'payment_method' => 'bank_transfer',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => ['status' => OrderStatus::Paid, 'paid_at' => now()]);
    }
}
