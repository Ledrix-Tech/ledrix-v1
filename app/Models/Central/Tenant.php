<?php

namespace App\Models\Central;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\{
    BelongsTo,
    HasMany,
    HasOne
};

class Tenant extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $connection = 'central';
    protected $table      = 'tenants';
    protected $guard      = 'tenant';

    protected $fillable = [
        // plan_id not package string
        'plan_id',
        'name',
        'slug',
        'email',
        'password',
        'phone',
        'country',
        'address',
        'website',
        'logo',
        'timezone',
        'billing_name',
        'billing_email',
        'billing_phone',
        'billing_address',
        'preferred_billing_currency',
        'auto_renew',
        'jazzcash_payment_token',
        'jazzcash_token_expires_at',
        'card_collected_at',
        'stripe_setup_intent_id',
        'stripe_customer_id',
        'stripe_payment_method_id',
        'custom_domain',
        'custom_domain_verified',
        'trial_ends_at',
        'trial_used',
        'status',
        'suspended_reason',
        'suspended_at',
        'meta',
        'registered_ip',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'stripe_payment_method_id',
        'jazzcash_payment_token',
    ];

    protected $casts = [
        'email_verified_at'      => 'datetime',
        'trial_ends_at'          => 'datetime',
        'suspended_at'           => 'datetime',
        'last_login_at'          => 'datetime',
        'trial_used'             => 'boolean',
        'auto_renew'             => 'boolean',
        'custom_domain_verified' => 'boolean',
        'jazzcash_token_expires_at' => 'datetime',
        'jazzcash_payment_token' => 'encrypted',
        'meta'                   => 'array',
    ];

    // ── Relationships ──────────────────────────────────────

    // Current plan this tenant is on
    public function plan(): BelongsTo
    {
        return $this->belongsTo(PackagePricing::class);
    }

    // All subscription history
    public function memberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    // Current active subscription only
    public function activeMembership(): HasOne
    {
        return $this->hasOne(TenantMembership::class)
                    ->whereIn('status', ['active', 'trialing'])
                    ->latestOfMany('start_date');
    }

    // All payments this tenant made to Ledrix
    public function payments(): HasMany
    {
        return $this->hasMany(TenantPayment::class);
    }

    // All invoices Ledrix issued to this tenant
    public function invoices(): HasMany
    {
        return $this->hasMany(TenantInvoice::class);
    }

    // Renewal requests
    public function renewalRequests(): HasMany
    {
        return $this->hasMany(TenantRenewalRequest::class);
    }

    // Per-tenant limit overrides
    // null columns = use plan default
    public function limitOverride(): HasOne
    {
        return $this->hasOne(TenantLimitOverride::class);
    }

    // Per-tenant feature overrides
    // null columns = use plan default
    public function featureOverride(): HasOne
    {
        return $this->hasOne(TenantFeatureOverride::class);
    }

    // Real-time usage counter
    public function usageSnapshot(): HasOne
    {
        return $this->hasOne(TenantUsageSnapshot::class);
    }

    // Platform webhook events for this tenant
    public function webhookEvents(): HasMany
    {
        return $this->hasMany(PlatformWebhookEvent::class);
    }

    // API tokens for public API access
    public function apiTokens(): HasMany
    {
        return $this->hasMany(TenantApiToken::class);
    }

    // All audit log entries for this tenant
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // Support tickets this tenant opened
    public function supportTickets(): HasMany
    {
        return $this->hasMany(PlatformSupportTicket::class);
    }

    // Per-tenant feature flags (legacy alias: companyFeatures)
    public function featureFlags(): HasMany
    {
        return $this->hasMany(TenantFeatureFlag::class);
    }

    /** @deprecated Use featureFlags() */
    public function companyFeatures(): HasMany
    {
        return $this->featureFlags();
    }

    // Referrals this tenant sent out
    public function referralsSent(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_tenant_id');
    }

    // Referral this tenant came from
    public function referredBy(): HasOne
    {
        return $this->hasOne(Referral::class, 'referred_tenant_id');
    }

    // ── Feature Resolution ─────────────────────────────────
    // Order: feature_override → plan → false
    // Usage: $tenant->can('webhooks')

    // public function can(string $feature): bool
    // {
    //     $key = 'feature_' . $feature;

    //     // 1. Check per-tenant override first
    //     $override = $this->featureOverride;
    //     if ($override && !is_null($override->$key)) {
    //         return (bool) $override->$key;
    //     }

    //     // 2. Fall back to plan column
    //     return (bool) ($this->plan?->$key ?? false);
    // }

    // ── Limit Resolution ───────────────────────────────────
    // Order: limit_override → plan → 0
    // Usage: $tenant->limit('max_brands')

    public function limit(string $limitKey): int
    {
        $override = $this->limitOverride;

        if ($override && ! $override->isExpired()) {
            $forced = $override->getOverride($limitKey);

            if ($forced !== null) {
                return $forced;
            }
        }

        return (int) ($this->plan?->$limitKey ?? 0);
    }

    public function isUnlimited(string $limitKey): bool
    {
        return $this->limit($limitKey) === -1;
    }

    // Check if tenant has hit a specific limit (live DB counts).
    // Usage: $tenant->hasHitLimit('max_brands')
    public function hasHitLimit(string $limitKey): bool
    {
        return app(\App\Services\Tenant\TenantUsageService::class)->hasHitLimit($this, $limitKey);
    }

    // ── Trial Helpers ──────────────────────────────────────

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at
            && now()->lt($this->trial_ends_at);
    }

    public function trialDaysLeft(): int
    {
        if (!$this->isOnTrial()) return 0;
        return (int) now()->diffInDays($this->trial_ends_at);
    }

    // ── Status Helpers ─────────────────────────────────────

    public function isActive(): bool
    {
        if ($this->status !== 'active') return false;
        return $this->activeMembership()->exists()
            || $this->isOnTrial();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isEmailVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function suspend(string $reason): void
    {
        $this->update([
            'status'           => 'suspended',
            'suspended_reason' => $reason,
            'suspended_at'     => now(),
        ]);
    }

    public function activate(): void
    {
        $this->update([
            'status'           => 'active',
            'suspended_reason' => null,
            'suspended_at'     => null,
        ]);
    }

    public function subscriptionExpiresSoon(int $days = 7): bool
    {
        $membership = $this->activeMembership;
        if (!$membership?->end_date) return false;
        return now()->diffInDays($membership->end_date) <= $days;
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBySlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }

    public function scopeByDomain($query, string $domain)
    {
        return $query->where('custom_domain', $domain)
                     ->orWhere('slug', explode('.', $domain)[0]);
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereHas('activeMembership', function ($q) use ($days) {
            $q->whereDate('end_date', '<=', now()->addDays($days))
              ->whereDate('end_date', '>=', now());
        });
    }

    protected static function booted(): void
    {
        static::updating(function (Tenant $tenant) {
            if ($tenant->isDirty('custom_domain') && filled($tenant->custom_domain)) {
                app(\App\Services\Tenant\TenantFeatureService::class)
                    ->assertEnabled('custom_domain', (int) $tenant->id);
            }
        });
    }
}
