<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property OrderStatus $status
 * @property string|null $note
 * @property Carbon $created_at
 */
#[Fillable(['order_id', 'status', 'note'])]
class OrderStatusHistory extends Model
{
    protected function casts(): array
    {
        return ['status' => OrderStatus::class];
    }
}
