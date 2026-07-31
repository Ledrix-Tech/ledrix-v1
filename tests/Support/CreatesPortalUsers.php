<?php

namespace Tests\Support;

use App\Models\Admin;
use App\Models\Brand;
use App\Models\Seller;
use App\Services\Tenant\TenantFeatureService;
use App\Support\TenantFeatureCatalog;

trait CreatesPortalUsers
{
    protected function createAdmin(array $attributes = []): Admin
    {
        return Admin::factory()->create(array_merge([
            'tenant_id' => 1,
        ], $attributes));
    }

    protected function createSellerUser(?Brand $brand = null, array $attributes = []): Seller
    {
        $brand ??= Brand::factory()->create(['tenant_id' => 1]);

        return Seller::factory()->create(array_merge([
            'brand_id'  => $brand->id,
            'tenant_id' => $brand->tenant_id ?? 1,
        ], $attributes));
    }

    /** Bypass subscription gate during portal smoke/security tests. */
    protected function mockCrmWorkspaceAccess(): void
    {
        $this->mock(\App\Services\Tenant\SubscriptionAccessService::class, function ($mock) {
            $mock->shouldReceive('canUseCrm')->andReturn(true);
        });
    }

    /** Bypass tenant feature gates on seller routes during smoke tests. */
    protected function mockTenantFeaturesEnabled(): void
    {
        $snapshot = collect(TenantFeatureCatalog::FEATURES)
            ->mapWithKeys(fn (array $definition) => [$definition['key'] => true])
            ->all();

        $this->mock(TenantFeatureService::class, function ($mock) use ($snapshot) {
            $mock->shouldReceive('enabled')->andReturn(true);
            $mock->shouldReceive('snapshot')->andReturn($snapshot);
            $mock->shouldReceive('anyEnabled')->andReturn(true);
            $mock->shouldReceive('hasLeadPrediction')->andReturn(false);
            $mock->shouldReceive('assertEnabled')->andReturnNull();
            $mock->shouldReceive('assertAnyEnabled')->andReturnNull();
            $mock->shouldReceive('assertProviderEnabled')->andReturnNull();
        });
    }
}
