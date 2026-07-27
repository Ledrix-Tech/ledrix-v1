<?php

namespace App\Support;

final class OrderStatus
{
    public const PENDING = 'pending';
    public const PARTIALLY_PAID = 'partially_paid';
    public const PAID = 'paid';
    public const CANCELLED = 'cancelled';

    public static function all(): array
    {
        return [
            self::PENDING,
            self::PARTIALLY_PAID,
            self::PAID,
            self::CANCELLED,
        ];
    }
}
