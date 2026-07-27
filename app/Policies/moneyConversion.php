<?php

if (!function_exists('money_cents')) {
    function money_cents(int $cents, string $currency = 'USD'): string
    {
        return number_format($cents / 100, 2) . ' ' . $currency;
    }
}
