<?php

namespace App\Mail;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Setting;
use App\Support\Receipt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the customer their order moved to a new step (paid, processing, shipped, delivered or cancelled).
 */
class OrderStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order, public ?string $note = null) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->order->status) {
            OrderStatus::Paid => 'Payment received for order '.$this->order->number,
            OrderStatus::Processing => 'We are packing your order '.$this->order->number,
            OrderStatus::Shipped => 'Your order '.$this->order->number.' is on its way',
            OrderStatus::Delivered => 'Your order '.$this->order->number.' has been delivered',
            OrderStatus::Cancelled => 'Your order '.$this->order->number.' was cancelled',
            OrderStatus::Pending => 'Update on order '.$this->order->number,
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-status-changed', with: ['store' => Setting::values()]);
    }

    /**
     * The receipt goes with the "payment received" email.
     *
     * @return array<int, \Illuminate\Mail\Attachment>
     */
    public function attachments(): array
    {
        return $this->order->status === OrderStatus::Paid ? [Receipt::attachment($this->order)] : [];
    }
}
