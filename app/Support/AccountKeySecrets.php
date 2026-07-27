<?php

namespace App\Support;

class AccountKeySecrets
{
    /** Fields stored encrypted and never shown in full in the admin UI. */
    public const SENSITIVE_FIELDS = [
        'stripe_secret_key',
        'stripe_webhook_secret',
        'paypal_secret',
    ];

    public static function mask(?string $value, int $prefix = 7, int $suffix = 4): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $length = strlen($value);

        if ($length <= $prefix + $suffix) {
            return str_repeat('•', $length);
        }

        return substr($value, 0, $prefix) . str_repeat('•', 8) . substr($value, -$suffix);
    }

    /**
     * Drop blank secret fields so existing values are not overwritten on update.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function omitBlankSecrets(array $payload): array
    {
        foreach (self::SENSITIVE_FIELDS as $field) {
            if (! array_key_exists($field, $payload)) {
                continue;
            }

            if ($payload[$field] === null || $payload[$field] === '') {
                unset($payload[$field]);
            }
        }

        return $payload;
    }
}
