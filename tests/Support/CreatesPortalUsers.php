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
        return Admin::factory()->create($attributes);
    }

    protected function createSellerUser(?Brand $brand = null, array $attributes = []): Seller
    {
        $brand ??= Brand::factory()->create();

        return Seller::factory()->create(array_merge([
            'brand_id' => $brand->id,
        ], $attributes));
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
