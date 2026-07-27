<?php

namespace App\Support;

final class PaymentProvider
{
    public const STRIPE = 'stripe';
    public const PAYPAL = 'paypal';

    public static function upworkAllowed(): array
    {
        return [
            self::STRIPE,
            self::PAYPAL,
        ];
    }
}
