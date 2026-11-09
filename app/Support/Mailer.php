<?php

namespace App\Support;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends email straight away (shared hosting has no queue worker). A mail server
 * problem is logged instead of breaking the order or form the customer just sent.
 */
class Mailer
{
    public static function send(string $to, Mailable $mail): bool
    {
        if (blank($to)) {
            return false;
        }

        try {
            Mail::to($to)->send($mail);

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
