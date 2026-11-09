<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Setting;
use App\Support\Receipt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Thank you for your order", with the PDF receipt attached.
 * Sent when a card payment succeeds, or straight away for bank transfer orders (with the bank details).
 */
class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your '.Setting::get('store_name').' order '.$this->order->number);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.order-placed', with: ['store' => Setting::values()]);
    }

    /**
     * @return array<int, \Illuminate\Mail\Attachment>
     */
    public function attachments(): array
    {
        return [Receipt::attachment($this->order)];
    }
}
