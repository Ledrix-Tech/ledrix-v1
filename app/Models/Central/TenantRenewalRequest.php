<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantRenewalRequest extends Model
{
    protected $connection = 'central';
    protected $table      = 'tenant_renewal_requests';

    protected $fillable = [
        'tenant_id', 'plan_id', 'token',
        'requested_by_email', 'billing_cycle',
        'amount', 'status',
        'expires_at', 'approved_at',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'approved_at' => 'datetime',
        'amount'      => 'decimal:2',
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

    // ── Helpers ────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at->isPast()
            || $this->status === 'expired';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending'
            && !$this->isExpired();
    }

    public function approve(): void
    {
        $this->update([
            'status'      => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function expire(): void
    {
        $this->update(['status' => 'expired']);
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending')
                     ->where('expires_at', '>', now());
    }
}