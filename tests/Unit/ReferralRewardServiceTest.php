<?php

namespace Tests\Unit;

use App\Models\Central\PackagePricing;
use App\Models\Central\Referral;
use App\Models\Central\Tenant;
use App\Services\Billing\ReferralRewardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class ReferralRewardServiceTest extends TestCase
{
    use RefreshDatabase;
    use UsesSqliteCentral;

    private ReferralRewardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->service = app(ReferralRewardService::class);
    }

    public function test_apply_to_invoice_amount_uses_discount_then_credit(): void
    {
        $tenant = $this->makeTenant([
            'meta' => [
                'referral_discount' => [
                    'type'     => 'amount',
                    'value'    => 20,
                    'currency' => 'USD',
                ],
                'billing_credits' => [
                    'USD' => 15,
                ],
            ],
        ]);

        $result = $this->service->applyToInvoiceAmount($tenant->fresh(), 100, 'USD');

        $this->assertSame(65.0, $result['amount']);
        $this->assertSame(20.0, $result['discount_applied']);
        $this->assertSame(15.0, $result['credit_applied']);

        $tenant->refresh();
        $this->assertArrayNotHasKey('referral_discount', $tenant->meta ?? []);
        $this->assertSame([], $tenant->meta['billing_credits'] ?? []);
    }

    public function test_discount_reward_is_fixed_amount_not_percent(): void
    {
        $tenant = $this->makeTenant();
        $referral = Referral::query()->create([
            'referrer_tenant_id' => $tenant->id,
            'referral_code'      => 'TESTABCD',
            'reward_type'        => 'discount',
            'reward_amount'      => 50,
            'currency'           => 'USD',
            'status'             => 'converted',
            'expires_at'         => now()->addMonth(),
        ]);

        $this->service->fulfill($referral->fresh());

        $tenant->refresh();
        $this->assertSame('amount', $tenant->meta['referral_discount']['type']);
        $this->assertSame(50.0, (float) $tenant->meta['referral_discount']['value']);
        $this->assertSame('rewarded', $referral->fresh()->status);
    }

    public function test_credit_reward_adds_billing_credit(): void
    {
        $tenant = $this->makeTenant();
        $referral = Referral::query()->create([
            'referrer_tenant_id' => $tenant->id,
            'referral_code'      => 'CRED1234',
            'reward_type'        => 'credit',
            'reward_amount'      => 40,
            'currency'           => 'USD',
            'status'             => 'converted',
            'expires_at'         => now()->addMonth(),
        ]);

        $this->service->fulfill($referral->fresh());

        $tenant->refresh();
        $this->assertSame(40.0, (float) $tenant->meta['billing_credits']['USD']);
    }

    public function test_cannot_reward_expired_referral(): void
    {
        $tenant = $this->makeTenant();
        $referral = Referral::query()->create([
            'referrer_tenant_id' => $tenant->id,
            'referral_code'      => 'EXPIRED1',
            'reward_type'        => 'credit',
            'reward_amount'      => 10,
            'currency'           => 'USD',
            'status'             => 'pending',
            'expires_at'         => now()->subDay(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->service->fulfill($referral->fresh());
    }

    private function makeTenant(array $overrides = []): Tenant
    {
        $plan = PackagePricing::query()->create([
            'name'          => 'Test Plan',
            'slug'          => 'test-plan-' . uniqid(),
            'monthly_price' => 99,
            'yearly_price'  => 990,
            'status'        => 'active',
            'is_public'     => true,
            'sort_order'    => 1,
            'trial_days'    => 14,
        ]);

        return Tenant::query()->create(array_merge([
            'plan_id'  => $plan->id,
            'name'     => 'Acme Co',
            'slug'     => 'acme-' . uniqid(),
            'email'    => 'acme-' . uniqid() . '@example.com',
            'password' => Hash::make('Password1!'),
            'country'  => 'US',
            'status'   => 'active',
            'meta'     => [],
        ], $overrides));
    }
}
