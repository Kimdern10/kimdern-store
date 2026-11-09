<?php

namespace Database\Factories;

use App\Models\DeliveryZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeliveryZone>
 */
class DeliveryZoneFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Lagos',
            'states' => ['Lagos'],
            'fee' => 250000,
            'free_over' => null,
            'eta' => '1–2 working days',
            'is_active' => true,
        ];
    }
}
