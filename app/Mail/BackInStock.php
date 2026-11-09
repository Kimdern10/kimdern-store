<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\StockAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a shopper that the item they asked about is back in stock.
 */
class BackInStock extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public StockAlert $alert) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->alert->product->name.' is back in stock');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.back-in-stock', with: ['store' => Setting::values()]);
    }
}
