<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class TenantApiToken extends Model
{
    protected $connection = 'central';
    protected $table      = 'tenant_api_tokens';

    protected $fillable = [
        'tenant_id',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'last_used_ip',
        'expires_at',
        'status',
    ];

    // Token never returned after creation
    protected $hidden = ['token'];

    protected $casts = [
        'abilities'    => 'array',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Token Generation ───────────────────────────────────

    // Generate a new token — returns plain text once
    // Stores hashed version in DB
    // Usage:
    //   [$token, $record] = TenantApiToken::generate($tenantId, 'My App', ['leads:read']);
    //   Show $token to user once, never again
    public static function generate(
        int    $tenantId,
        string $name,
        array  $abilities = ['*']
    ): array {
        $plain = Str::random(64);

        $record = static::create([
            'tenant_id' => $tenantId,
            'name'      => $name,
            'token'     => hash('sha256', $plain),
            'abilities' => $abilities,
            'status'    => 'active',
        ]);

        return [$plain, $record];
    }

    // Find token record from plain token in request header
    public static function findByPlainToken(string $plain): ?self
    {
        return static::where('token', hash('sha256', $plain))
                     ->where('status', 'active')
                     ->first();
    }

    // ── Ability Helpers ────────────────────────────────────

    // Check if token has a specific ability
    // Usage: $token->can('leads:read')
    public function can(string $ability): bool
    {
        $abilities = $this->abilities ?? [];
        return in_array('*', $abilities)
            || in_array($ability, $abilities);
    }

    // ── Status Helpers ─────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isRevoked();
    }

    public function revoke(): void
    {
        $this->update(['status' => 'revoked']);
    }

    // Record last usage on every API request
    public function recordUsage(): void
    {
        $this->update([
            'last_used_at' => now(),
            'last_used_ip' => request()->ip(),
        ]);
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where(fn($q) => $q
                         ->whereNull('expires_at')
                         ->orWhere('expires_at', '>', now()));
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}