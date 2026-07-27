<?php

namespace App\Support;

final class LinkStatus
{
    public const ACTIVE = 'active';
    public const EXPIRED = 'expired';
    public const PAID = 'paid';
    public const CANCELLED = 'cancelled';

    public static function all(): array
    {
        return [
            self::ACTIVE,
            self::EXPIRED,
            self::PAID,
            self::CANCELLED,
        ];
    }
}
