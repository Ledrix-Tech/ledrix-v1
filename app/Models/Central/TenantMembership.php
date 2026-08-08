<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{
    BelongsTo,
    HasMany,
    HasOne
};

class TenantMembership extends Model
{
    protected $connection = 'central';
    protected $table      = 'tenant_memberships';

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'billing_cycle',
        'stripe_subscription_id',
        'amount',
        'currency',
        'api_key',
        'start_date',
        'end_date',
        'trial_start',
        'trial_end',
        'cancelled_at',
        'cancel_reason',
        'renewed_by',
        'trial_reminder_sent_at',
        'renewal_reminder_7d_sent_at',
        'renewal_reminder_3d_sent_at',
        'renewal_reminder_1d_sent_at',
        'renewal_expired_notice_sent_at',
        'conversion_source',
        'status',
        'meta',
    ];

    protected $casts = [
        'start_date'                    => 'date',
        'end_date'                      => 'date',
        'trial_start'                   => 'date',
        'trial_end'                     => 'date',
        'cancelled_at'                  => 'datetime',
        'trial_reminder_sent_at'        => 'datetime',
        'renewal_reminder_7d_sent_at'   => 'datetime',
        'renewal_reminder_3d_sent_at'   => 'datetime',
        'renewal_reminder_1d_sent_at'   => 'datetime',
        'renewal_expired_notice_sent_at'=> 'datetime',
        'amount'                        => 'decimal:2',
        'meta'                          => 'array',
    ];

    // ── Relationships ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PackagePricing::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->status));
    }

    // All payments linked to this membership
    public function payments(): HasMany
    {
        return $this->hasMany(TenantPayment::class, 'membership_id');
    }

    // Invoice for this membership period
    public function invoice(): HasOne
    {
        return $this->hasOne(TenantInvoice::class, 'membership_id');
    }

    // ── Helpers ────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trialing'
            && $this->trial_end
            && now()->lt($this->trial_end);
    }

    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function isPastDue(): bool
    {
        return $this->status === 'past_due';
    }

    public function daysUntilExpiry(): int
    {
        if (!$this->end_date) return 0;
        return max(0, (int) now()->diffInDays($this->end_date, false));
    }

    public function expiresSoon(int $days = 7): bool
    {
        return $this->daysUntilExpiry() <= $days
            && !$this->isExpired();
    }

    public function cancel(string $reason = ''): void
    {
        $this->update([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancel_reason' => $reason,
        ]);
    }

    public function clearRenewalReminderTimestamps(): void
    {
        $this->update([
            'renewal_reminder_7d_sent_at'    => null,
            'renewal_reminder_3d_sent_at'    => null,
            'renewal_reminder_1d_sent_at'    => null,
            'renewal_expired_notice_sent_at' => null,
        ]);
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['active', 'trialing']);
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->whereDate('end_date', '<=', now()->addDays($days))
            ->whereDate('end_date', '>=', now())
            ->where('status', 'active');
    }
}
