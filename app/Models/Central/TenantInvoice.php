<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantInvoice extends Model
{
    protected $connection = 'central';
    protected $table      = 'tenant_invoices';

    protected $fillable = [
        'tenant_id', 'membership_id', 'payment_id',
        'invoice_number', 'plan_name', 'billing_cycle',
        'amount', 'currency', 'tax_amount', 'total_amount',
        'status', 'pdf_path',
        'issued_at', 'due_at', 'paid_at', 'notes',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'tax_amount'   => 'decimal:2',
        'total_amount' => 'decimal:2',
        'issued_at'    => 'datetime',
        'due_at'       => 'datetime',
        'paid_at'      => 'datetime',
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

    public function payment(): BelongsTo
    {
        return $this->belongsTo(TenantPayment::class, 'payment_id');
    }

    // ── Helpers ────────────────────────────────────────────

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->due_at
            && now()->gt($this->due_at)
            && $this->status !== 'paid';
    }

    // Auto-generate next sequential number LDX-2026-0001
    public static function nextNumber(): string
    {
        $year = now()->year;
        $last = static::whereYear('created_at', $year)
                       ->max('invoice_number');
        $seq  = $last ? ((int) substr($last, -4)) + 1 : 1;
        return 'LDX-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    // ── Scopes ────────────────────────────────────────────

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'issued')
                     ->where('due_at', '<', now());
    }
}