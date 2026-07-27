<?php

namespace Tests\Unit;

use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;
use App\Services\Tenant\SubscriptionAccessService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;
use Tests\TestCase;

class SubscriptionAccessServiceTest extends TestCase
{
    private SubscriptionAccessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SubscriptionAccessService();
        config(['subscription.early_renew_days' => 7]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_needs_payment_when_active_membership_is_past_end_date(): void
    {
        $membership = $this->membershipStub('active', now()->subDay());
        $tenant = $this->tenantStub($membership, onTrial: false);

        $this->assertTrue($this->service->needsPayment($tenant));
        $this->assertFalse($this->service->canUseCrm($tenant));
    }

    public function test_can_pay_on_billing_for_early_renewal_within_window(): void
    {
        $membership = $this->membershipStub('active', now()->addDays(5));
        $tenant = $this->tenantStub($membership, onTrial: false);

        $this->assertTrue($this->service->canPayOnBilling($tenant));
        $this->assertTrue($this->service->expiresSoon($tenant));
        $this->assertFalse($this->service->needsPayment($tenant));
        $this->assertSame('renewal', $this->service->paymentOrderType($tenant));
    }

    public function test_cannot_pay_on_billing_when_subscription_is_not_due_soon(): void
    {
        $membership = $this->membershipStub('active', now()->addDays(20));
        $tenant = $this->tenantStub($membership, onTrial: false);

        $this->assertFalse($this->service->canPayOnBilling($tenant));
        $this->assertFalse($this->service->expiresSoon($tenant));
    }

    public function test_needs_payment_for_past_due_and_expired_statuses(): void
    {
        foreach (['past_due', 'expired'] as $status) {
            $membership = $this->membershipStub($status, now()->subDays(3));
            $tenant = $this->tenantStub($membership, onTrial: false);

            $this->assertTrue($this->service->needsPayment($tenant));
            $this->assertTrue($this->service->canPayOnBilling($tenant));
        }
    }

    private function tenantStub(TenantMembership $membership, bool $onTrial): Tenant
    {
        $builder = Mockery::mock(Builder::class);
        $builder->shouldReceive('first')->andReturn($membership);

        $relation = Mockery::mock(HasMany::class);
        $relation->shouldReceive('latest')->with('start_date')->andReturn($builder);

        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->shouldReceive('isSuspended')->andReturn(false);
        $tenant->shouldReceive('isCancelled')->andReturn(false);
        $tenant->shouldReceive('isEmailVerified')->andReturn(true);
        $tenant->shouldReceive('isOnTrial')->andReturn($onTrial);
        $tenant->shouldReceive('memberships')->andReturn($relation);

        return $tenant;
    }

    private function membershipStub(string $status, Carbon $endDate): TenantMembership
    {
        $membership = new TenantMembership([
            'status'   => $status,
            'end_date' => $endDate->toDateString(),
        ]);
        $membership->syncOriginal();

        return $membership;
    }
}
