<?php

namespace App\Support;

final class ModuleType
{
    public const UPWORK = 'upwork';
    public const PPC = 'ppc';

    public static function all(): array
    {
        return [
            self::UPWORK,
            self::PPC,
        ];
    }
}
