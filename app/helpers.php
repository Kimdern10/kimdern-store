<?php

if (! function_exists('naira')) {
    /**
     * Format kobo as naira, e.g. 1250000 → "₦12,500".
     */
    function naira(int|float|null $kobo, bool $showKobo = false): string
    {
        $amount = ((int) $kobo) / 100;

        return '₦'.number_format($amount, $showKobo || fmod($amount, 1.0) !== 0.0 ? 2 : 0);
    }
}
