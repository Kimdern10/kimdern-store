<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(8)),
            'type' => 'percent',
            'value' => 10,
            'min_subtotal' => 0,
            'max_uses' => null,
            'is_active' => true,
        ];
    }

    public function fixed(int $kobo): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'fixed', 'value' => $kobo]);
    }
}
