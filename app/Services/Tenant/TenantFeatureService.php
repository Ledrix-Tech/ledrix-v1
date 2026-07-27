<?php

namespace App\Services\Tenant;

use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use App\Models\Central\TenantFeatureOverride;
use App\Support\TenantContext;
use App\Support\TenantFeatureCatalog;

class TenantFeatureService
{
    /**
     * Whether a tenant feature is enabled.
     * Resolution: per-tenant override → plan column → legacy feature flag.
     */
    public function enabled(string $feature, ?int $tenantId = null): bool
    {
        $tenantId = $tenantId ?? TenantContext::resolve();

        if (! $tenantId) {
            return false;
        }

        $column = $this->resolveColumn($feature);

        if (! $column) {
            return false;
        }

        $tenant = Tenant::query()
            ->with(['plan', 'featureOverride'])
            ->find($tenantId);

        if (! $tenant) {
            return false;
        }

        $override = $tenant->featureOverride;

        if ($override instanceof TenantFeatureOverride && ! $override->isExpired()) {
            $forced = $override->getOverride($this->stripFeaturePrefix($column));

            if ($forced !== null) {
                return $forced;
            }
        }

        if ($tenant->plan instanceof PackagePricing && $tenant->plan->hasFeature($this->stripFeaturePrefix($column))) {
            return true;
        }

        return $this->legacyFlagEnabled($tenant, $feature);
    }

    /**
     * @return array<string, bool> Short-key => enabled
     */
    public function snapshot(?int $tenantId = null): array
    {
        $snapshot = [];

        foreach (TenantFeatureCatalog::FEATURES as $definition) {
            $snapshot[$definition['key']] = $this->enabled($definition['key'], $tenantId);
        }

        return $snapshot;
    }

    public function anyEnabled(array $features, ?int $tenantId = null): bool
    {
        foreach ($features as $feature) {
            if ($this->enabled($feature, $tenantId)) {
                return true;
            }
        }

        return false;
    }

    public function assertEnabled(string $feature, ?int $tenantId = null, ?string $message = null): void
    {
        abort_unless(
            $this->enabled($feature, $tenantId),
            403,
            $message ?? $this->denialMessage($feature)
        );
    }

    /**
     * @param  list<string>  $features
     */
    public function assertAnyEnabled(array $features, ?string $message = null, ?int $tenantId = null): void
    {
        abort_unless(
            $this->anyEnabled($features, $tenantId),
            403,
            $message ?? 'Payment processing is not included in your subscription plan.'
        );
    }

    public function assertProviderEnabled(string $provider, ?int $tenantId = null): void
    {
        $feature = match ($provider) {
            'stripe' => 'stripe',
            'paypal' => 'paypal',
            default  => null,
        };

        abort_if($feature === null, 422, 'Invalid payment provider.');

        $this->assertEnabled($feature, $tenantId);
    }

    private function denialMessage(string $feature): string
    {
        $definition = collect(TenantFeatureCatalog::FEATURES)
            ->first(fn ($def) => $def['key'] === $feature || $def['column'] === $feature);

        $label = $definition['label'] ?? ucfirst(str_replace('_', ' ', $feature));

        return "{$label} is not included in your subscription plan. Contact your administrator to upgrade.";
    }

    public function hasLeadPrediction(?int $tenantId = null): bool
    {
        return $this->enabled('lead_prediction', $tenantId);
    }

    /**
     * @return list<array{
     *     key: string,
     *     column: string,
     *     label: string,
     *     group: string,
     *     description: string,
     *     plan_default: bool,
     *     override: bool|null,
     *     effective: bool
     * }>
     */
    public function matrixForTenant(Tenant $tenant): array
    {
        $tenant->loadMissing(['plan', 'featureOverride']);
        $override = $tenant->featureOverride;
        $overrideActive = $override instanceof TenantFeatureOverride && ! $override->isExpired();

        $rows = [];

        foreach (TenantFeatureCatalog::FEATURES as $definition) {
            $column = $definition['column'];
            $planDefault = (bool) ($tenant->plan?->$column ?? false);
            $overrideValue = $overrideActive ? $override->{$column} : null;
            $effective = $overrideValue !== null ? (bool) $overrideValue : $planDefault;

            $rows[] = array_merge($definition, [
                'plan_default' => $planDefault,
                'override'     => $overrideValue,
                'effective'    => $effective,
            ]);
        }

        return $rows;
    }

    private function resolveColumn(string $feature): ?string
    {
        if (str_starts_with($feature, 'feature_')) {
            return array_key_exists($feature, TenantFeatureCatalog::FEATURES) ? $feature : null;
        }

        foreach (TenantFeatureCatalog::FEATURES as $column => $definition) {
            if ($definition['key'] === $feature) {
                return $column;
            }
        }

        return null;
    }

    private function stripFeaturePrefix(string $column): string
    {
        return str_starts_with($column, 'feature_')
            ? substr($column, strlen('feature_'))
            : $column;
    }

    private function legacyFlagEnabled(Tenant $tenant, string $feature): bool
    {
        if (! in_array($feature, ['lead_prediction', 'leads-classify'], true)) {
            return false;
        }

        return $tenant->featureFlags()
            ->where('is_enabled', true)
            ->whereIn('feature_key', ['leads-classify', 'lead_prediction'])
            ->exists();
    }
}
