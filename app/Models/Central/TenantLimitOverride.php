<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantLimitOverride extends Model
{
    protected $connection = 'central';
    protected $table      = 'tenant_limit_overrides';

    protected $fillable = [
        'tenant_id',
        'max_brands',
        'max_sellers',
        'max_admins',
        'max_clients',
        'max_leads_per_month',
        'max_orders',
        'max_payment_links',
        'max_account_keys',
        'max_projects',
        'max_storage_mb',
        'override_reason',
        'overridden_by',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function overriddenBy(): BelongsTo
    {
        return $this->belongsTo(SuperAdmin::class, 'overridden_by');
    }

    // ── Helpers ────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    // Get override value for a key
    // Returns null if no override or expired
    // Returns -1 if unlimited override
    // Returns integer if specific override
    public function getOverride(string $key): ?int
    {
        if ($this->isExpired()) return null;
        return isset($this->$key)
            ? (int) $this->$key
            : null;
    }

    public function isUnlimited(string $key): bool
    {
        return $this->getOverride($key) === -1;
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
}