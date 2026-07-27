<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TenantEmailVerification extends Model
{
    protected $connection = 'central';
    protected $table      = 'tenant_email_verifications';

    public $timestamps = false;

    protected $fillable = [
        'tenant_id',
        'email',
        'token',
        'expires_at',
        'created_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Helpers ────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    // Generate a fresh verification record
    // Deletes any previous token for this tenant first
    // Returns the record with plain token readable once
    public static function generate(
        int    $tenantId,
        string $email,
        int    $expiresInHours = 24
    ): self {
        // Remove existing token for this tenant
        static::where('tenant_id', $tenantId)->delete();

        return static::create([
            'tenant_id'  => $tenantId,
            'email'      => $email,
            'token'      => Str::random(64),
            'expires_at' => now()->addHours($expiresInHours),
            'created_at' => now(),
        ]);
    }

    public static function verify(string $token): bool
    {
        $service = app(\App\Services\Tenant\VerifyTenantEmailService::class);

        return (bool) $service->verify($token);
    }
}