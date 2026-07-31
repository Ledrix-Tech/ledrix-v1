<?php

namespace Tests\Unit;

use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use App\Services\Tenant\TenantLimitService;
use App\Services\Tenant\TenantUsageService;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TenantLimitServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_usage_service_detects_limit_reached_from_live_counts(): void
    {
        $tenant = $this->tenantWithPlan(maxSellers: 2);

        $usage = Mockery::mock(TenantUsageService::class)->makePartial();
        $usage->shouldReceive('countForLimit')->with('max_sellers', 1)->andReturn(2);

        $this->assertTrue($usage->hasHitLimit($tenant, 'max_sellers'));
    }

    public function test_assert_can_create_seller_aborts_when_limit_reached(): void
    {
        $tenant = $this->tenantWithPlan(maxSellers: 1);

        $usage = Mockery::mock(TenantUsageService::class)->makePartial();
        $usage->shouldReceive('hasHitLimit')->andReturn(true);

        $this->app->instance(TenantUsageService::class, $usage);

        $this->expectException(HttpException::class);
        app(TenantLimitService::class)->assertCanCreateSeller(1);
    }

    public function test_unlimited_plan_never_hits_limit(): void
    {
        $tenant = $this->tenantWithPlan(maxSellers: -1);

        $usage = app(TenantUsageService::class);
        $this->assertFalse($usage->hasHitLimit($tenant, 'max_sellers'));
    }

    private function tenantWithPlan(int $maxSellers): Tenant
    {
        $plan = new PackagePricing(['max_sellers' => $maxSellers]);
        $tenant = new Tenant();
        $tenant->forceFill(['id' => 1]);
        $tenant->setRelation('plan', $plan);
        $tenant->setRelation('limitOverride', null);

        return $tenant;
    }
}
