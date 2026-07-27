<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Encrypts secrets at rest while still reading legacy plain-text values
 * until the hardening migration has encrypted existing rows.
 */
class LegacyEncryptedString implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value)) {
            $value = (string) $value;
        }

        try {
            Crypt::decryptString($value);

            // Already encrypted in DB — store unchanged.
            return $value;
        } catch (DecryptException) {
            return Crypt::encryptString($value);
        }
    }
}
