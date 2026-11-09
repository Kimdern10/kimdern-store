<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the shop owner a customer says they have sent a bank transfer, so they can check and confirm it.
 */
class TransferReported extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Check your bank: '.naira($this->order->total).' sent for order '.$this->order->number);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.transfer-reported');
    }
}
