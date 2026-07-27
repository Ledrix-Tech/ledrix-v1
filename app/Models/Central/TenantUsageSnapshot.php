<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantUsageSnapshot extends Model
{
    protected $connection = 'central';
    protected $table      = 'tenant_usage_snapshots';

    protected $fillable = [
        'tenant_id',
        'total_brands',
        'total_sellers',
        'total_admins',
        'total_clients',
        'total_orders',
        'total_payment_links',
        'total_account_keys',
        'total_projects',
        'leads_this_month',
        'month_reset_at',
        'storage_used_mb',
        'last_synced_at',
    ];

    protected $casts = [
        'month_reset_at'  => 'datetime',
        'last_synced_at'  => 'datetime',
    ];

    // ── Relationships ──────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Usage Check Helpers ────────────────────────────────

    // Check if tenant has hit a specific limit
    // Maps limit key → usage key automatically
    // Usage: $snapshot->hasHitLimit('max_brands', $tenant)
    public function hasHitLimit(string $limitKey, Tenant $tenant): bool
    {
        if ($tenant->isUnlimited($limitKey)) return false;

        $usageKey = $this->limitToUsageKey($limitKey);
        if (!$usageKey) return false;

        return (int) $this->$usageKey >= $tenant->limit($limitKey);
    }

    // How many slots are remaining for a limit
    public function remaining(string $limitKey, Tenant $tenant): int
    {
        if ($tenant->isUnlimited($limitKey)) return PHP_INT_MAX;

        $usageKey = $this->limitToUsageKey($limitKey);
        if (!$usageKey) return 0;

        $limit = $tenant->limit($limitKey);
        $used  = (int) $this->$usageKey;

        return max(0, $limit - $used);
    }

    // Percentage of limit used — for progress bars
    public function percentUsed(string $limitKey, Tenant $tenant): float
    {
        if ($tenant->isUnlimited($limitKey)) return 0.0;

        $limit    = $tenant->limit($limitKey);
        $usageKey = $this->limitToUsageKey($limitKey);

        if (!$usageKey || $limit <= 0) return 0.0;

        return round(((int) $this->$usageKey / $limit) * 100, 1);
    }

    // Increment a usage counter safely
    // Usage: $snapshot->increment('total_brands')
    public function incrementUsage(string $usageKey, int $by = 1): void
    {
        $this->increment($usageKey, $by);
        $this->update(['last_synced_at' => now()]);
    }

    // Decrement a usage counter safely — never below zero
    public function decrementUsage(string $usageKey, int $by = 1): void
    {
        $current = (int) $this->$usageKey;
        $this->update([
            $usageKey        => max(0, $current - $by),
            'last_synced_at' => now(),
        ]);
    }

    // Reset monthly lead counter
    // Called by scheduler at start of each month
    public function resetMonthlyLeads(): void
    {
        $this->update([
            'leads_this_month' => 0,
            'month_reset_at'   => now(),
            'last_synced_at'   => now(),
        ]);
    }

    // ── Private Helpers ────────────────────────────────────

    // Map plan limit key → snapshot usage key
    private function limitToUsageKey(string $limitKey): ?string
    {
        $map = [
            'max_brands'          => 'total_brands',
            'max_sellers'         => 'total_sellers',
            'max_admins'          => 'total_admins',
            'max_clients'         => 'total_clients',
            'max_orders'          => 'total_orders',
            'max_payment_links'   => 'total_payment_links',
            'max_account_keys'    => 'total_account_keys',
            'max_projects'        => 'total_projects',
            'max_leads_per_month' => 'leads_this_month',
            'max_storage_mb'      => 'storage_used_mb',
        ];

        return $map[$limitKey] ?? null;
    }
}