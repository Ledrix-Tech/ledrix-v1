<?php

use App\Services\Tenant\TenantFeatureService;

if (! function_exists('tenantFeature')) {
    function tenantFeature(string $feature): bool
    {
        return app(TenantFeatureService::class)->enabled($feature);
    }
}

if (! function_exists('tenantFeatures')) {
    /** @return array<string, bool> */
    function tenantFeatures(): array
    {
        return app(TenantFeatureService::class)->snapshot();
    }
}

if (! function_exists('tenantHasPayments')) {
    function tenantHasPayments(): bool
    {
        return app(TenantFeatureService::class)->anyEnabled(['stripe', 'paypal']);
    }
}
