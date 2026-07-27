<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Referral extends Model
{
    protected $connection = 'central';
    protected $table      = 'referrals';

    protected $fillable = [
        'referrer_tenant_id',
        'referred_tenant_id',
        'referral_code',
        'reward_type',
        'reward_amount',
        'currency',
        'status',
        'converted_at',
        'rewarded_at',
        'expires_at',
    ];

    protected $casts = [
        'reward_amount' => 'decimal:2',
        'converted_at'  => 'datetime',
        'rewarded_at'   => 'datetime',
        'expires_at'    => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────

    public function referrer(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'referrer_tenant_id');
    }

    public function referred(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'referred_tenant_id');
    }

    // ── Code Generation ────────────────────────────────────

    // Generate unique referral code from tenant name
    // Example: "Nova Agency" → "NOVA8X2K"
    public static function generateCode(string $tenantName): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $tenantName), 0, 4));
        do {
            $code = $prefix . strtoupper(Str::random(4));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    // ── Status Helpers ─────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    public function convert(int $referredTenantId): void
    {
        $this->update([
            'referred_tenant_id' => $referredTenantId,
            'status'             => 'converted',
            'converted_at'       => now(),
        ]);
    }

    public function reward(): void
    {
        $this->update([
            'status'      => 'rewarded',
            'rewarded_at' => now(),
        ]);
    }

    public function expire(): void
    {
        $this->update(['status' => 'expired']);
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                     ->where(fn($q) => $q
                         ->whereNull('expires_at')
                         ->orWhere('expires_at', '>', now()));
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    public function scopeForReferrer($query, int $tenantId)
    {
        return $query->where('referrer_tenant_id', $tenantId);
    }
}