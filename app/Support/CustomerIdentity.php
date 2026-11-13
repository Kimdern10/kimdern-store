<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Turns emails and phone numbers into a standard form, so one person can't look like
 * a new customer by writing their details slightly differently.
 */
class CustomerIdentity
{
    /**
     * "Ada.Obi+promo@Gmail.com" → "adaobi@gmail.com". For Gmail, dots and "+anything" don't make a new inbox.
     */
    public static function email(?string $email): ?string
    {
        $email = Str::lower(trim((string) $email));

        if (! str_contains($email, '@')) {
            return $email === '' ? null : $email;
        }

        [$name, $domain] = explode('@', $email, 2);
        $name = Str::before($name, '+');

        if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            $name = str_replace('.', '', $name);
            $domain = 'gmail.com';
        }

        return $name.'@'.$domain;
    }

    /**
     * "+234 803 123 4567" and "0803-123-4567" → "8031234567" (the last 10 digits).
     */
    public static function phone(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return strlen($digits) >= 10 ? substr($digits, -10) : null;
    }
}
