<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantFeatureOverride extends Model
{
    protected $connection = 'central';
    protected $table      = 'tenant_feature_overrides';

    protected $fillable = [
        'tenant_id',
        'feature_ppc_module',
        'feature_upwork_module',
        'feature_milestone_payments',
        'feature_stripe',
        'feature_paypal',
        'feature_webhooks',
        'feature_chargeback_tracking',
        'feature_dual_invoicing',
        'feature_client_portal',
        'feature_lead_prediction',
        'feature_seller_leaderboard',
        'feature_performance_bonus',
        'feature_projects',
        'feature_support_tickets',
        'feature_api_access',
        'feature_custom_domain',
        'feature_white_label',
        'override_reason',
        'overridden_by',
        'expires_at',
    ];

    protected $casts = [
        'expires_at'                    => 'datetime',
        'feature_ppc_module'            => 'boolean',
        'feature_upwork_module'         => 'boolean',
        'feature_milestone_payments'    => 'boolean',
        'feature_stripe'                => 'boolean',
        'feature_paypal'                => 'boolean',
        'feature_webhooks'              => 'boolean',
        'feature_chargeback_tracking'   => 'boolean',
        'feature_dual_invoicing'        => 'boolean',
        'feature_client_portal'         => 'boolean',
        'feature_lead_prediction'       => 'boolean',
        'feature_seller_leaderboard'    => 'boolean',
        'feature_performance_bonus'     => 'boolean',
        'feature_projects'              => 'boolean',
        'feature_support_tickets'       => 'boolean',
        'feature_api_access'            => 'boolean',
        'feature_custom_domain'         => 'boolean',
        'feature_white_label'           => 'boolean',
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

    // Returns null  = no override set for this feature
    // Returns true  = force enabled regardless of plan
    // Returns false = force disabled regardless of plan
    public function getOverride(string $feature): ?bool
    {
        if ($this->isExpired()) {
            return null;
        }

        $key = 'feature_' . ltrim($feature, 'feature_');

        if (! array_key_exists($key, $this->attributes) || $this->attributes[$key] === null) {
            return null;
        }

        return (bool) $this->attributes[$key];
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