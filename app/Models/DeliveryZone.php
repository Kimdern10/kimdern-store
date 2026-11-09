<?php

namespace App\Models;

use Database\Factories\DeliveryZoneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A group of states that share one delivery fee.
 *
 * @property int $id
 * @property string $name
 * @property list<string> $states
 * @property int $fee
 * @property int|null $free_over
 * @property string|null $eta
 * @property bool $is_active
 * @property int $sort_order
 */
#[Fillable(['name', 'states', 'fee', 'free_over', 'eta', 'is_active', 'sort_order'])]
class DeliveryZone extends Model
{
    /** @use HasFactory<DeliveryZoneFactory> */
    use HasFactory;

    public const array STATES = [
        'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno', 'Cross River', 'Delta',
        'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT - Abuja', 'Gombe', 'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina',
        'Kebbi', 'Kogi', 'Kwara', 'Lagos', 'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers',
        'Sokoto', 'Taraba', 'Yobe', 'Zamfara',
    ];

    protected function casts(): array
    {
        return ['states' => 'array', 'fee' => 'integer', 'free_over' => 'integer', 'is_active' => 'boolean'];
    }

    /**
     * The active zone that delivers to this state, if any.
     */
    public static function forState(?string $state): ?self
    {
        if (blank($state)) {
            return null;
        }

        return static::query()->where('is_active', true)->orderBy('sort_order')->get()
            ->first(fn (DeliveryZone $zone) => in_array($state, $zone->states, true));
    }

    /**
     * The fee for an order of this size (free above the zone's limit).
     */
    public function feeFor(int $subtotal): int
    {
        return $this->free_over !== null && $subtotal >= $this->free_over ? 0 : $this->fee;
    }
}
