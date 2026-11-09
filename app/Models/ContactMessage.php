<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A message sent from the contact page.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $subject
 * @property string $message
 * @property string|null $order_number
 * @property Carbon|null $read_at
 * @property Carbon $created_at
 */
#[Fillable(['name', 'email', 'phone', 'subject', 'message', 'order_number', 'read_at'])]
class ContactMessage extends Model
{
    public const array SUBJECTS = [
        'order' => 'A question about my order',
        'product' => 'A question about a product',
        'returns' => 'Returns or exchanges',
        'wholesale' => 'Wholesale / bulk buying',
        'other' => 'Something else',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function subjectLabel(): string
    {
        return self::SUBJECTS[$this->subject] ?? $this->subject;
    }
}
