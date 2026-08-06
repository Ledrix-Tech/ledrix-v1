<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PlatformBillingSetting extends Model
{
    protected $connection = 'central';

    protected $table = 'platform_billing_settings';

    protected $fillable = [
        'provider',
        'enabled',
        'credentials',
        'updated_by',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /** @return array<string, mixed> */
    public function getCredentialsArrayAttribute(): array
    {
        $raw = $this->attributes['credentials'] ?? null;

        if (! $raw) {
            return [];
        }

        try {
            $decoded = json_decode(Crypt::decryptString($raw), true);

            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable) {
            // Plain JSON fallback for local/dev rows that were never encrypted.
            $decoded = json_decode((string) $raw, true);

            return is_array($decoded) ? $decoded : [];
        }
    }

    /** @param  array<string, mixed>|null  $value */
    public function setCredentialsAttribute(?array $value): void
    {
        if ($value === null || $value === []) {
            $this->attributes['credentials'] = null;

            return;
        }

        $this->attributes['credentials'] = Crypt::encryptString(json_encode($value));
    }
}
