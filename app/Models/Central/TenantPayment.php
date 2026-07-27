<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{
    BelongsTo, HasOne
};

class TenantPayment extends Model
{
    protected $connection = 'central';
    protected $table      = 'tenant_payments';

    protected $fillable = [
        'tenant_id', 'membership_id', 'plan_id',
        'transaction_id', 'payment_intent_id',
        'gateway', 'order_type', 'renewed_by', 'billing_cycle',
        'amount', 'refunded_amount', 'currency',
        'refund_status', 'status',
        'paid_at', 'payload',
    ];

    protected $casts = [
        'amount'          => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'paid_at'         => 'datetime',
        'payload'         => 'array',
    ];

    // ── Relationships ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(TenantMembership::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(PackagePricing::class);
    }

    // Invoice generated from this payment
    public function invoice(): HasOne
    {
        return $this->hasOne(TenantInvoice::class, 'payment_id');
    }

    // ── Helpers ────────────────────────────────────────────

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRefunded(): bool
    {
        return in_array($this->refund_status, ['partial', 'full']);
    }

    public function netAmount(): float
    {
        return (float) $this->amount - (float) $this->refunded_amount;
    }

    public function customerReportedTxnId(): ?string
    {
        $id = $this->payload['customer_reported_txn_id'] ?? null;

        return $id ? (string) $id : null;
    }

    public function hasCustomerPaymentReport(): bool
    {
        return $this->customerReportedTxnId() !== null;
    }

    public function customerReportedAt(): ?string
    {
        return $this->payload['customer_reported_at'] ?? null;
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeByGateway($query, string $gateway)
    {
        return $query->where('gateway', $gateway);
    }
}