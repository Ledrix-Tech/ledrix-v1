<?php

namespace Tests\Feature;

use App\Models\Central\Tenant;
use App\Services\Tenant\SubscriptionAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\Support\CreatesPortalUsers;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

/**
 * Expired tenants must still reach Admin Organization Billing to renew.
 *
 * @group admin
 * @group billing
 */
class AdminExpiredBillingAccessTest extends TestCase
{
    use CreatesPortalUsers;
    use RefreshDatabase;
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->ensureTenantBillingColumns();
        $this->mockTenantFeaturesEnabled();
    }

    public function test_expired_admin_is_redirected_from_crm_to_org_billing(): void
    {
        $tenant = $this->makeVerifiedTenant();
        $admin = $this->createAdmin([
            'role'      => 'admin',
            'tenant_id' => $tenant->id,
        ]);

        $this->mockExpiredOrgBillingAccess();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.index.get'))
            ->assertRedirect(route('admin.org.billing'));
    }

    public function test_org_billing_is_not_gated_by_ppc_module_feature(): void
    {
        $middleware = app('router')->getRoutes()->getByName('admin.org.billing')->gatherMiddleware();

        $this->assertNotContains('tenant.feature:ppc_module', $middleware);
        $this->assertContains('crm.workspace', $middleware);
        $this->assertContains('admin.only', $middleware);
    }

    public function test_expired_admin_login_lands_on_org_billing(): void
    {
        $tenant = $this->makeVerifiedTenant();
        $admin = $this->createAdmin([
            'role'      => 'admin',
            'tenant_id' => $tenant->id,
            'email'     => 'expired-admin@example.com',
            'password'  => Hash::make('Secret123!'),
        ]);

        $this->mockExpiredOrgBillingAccess();

        $this->post(route('admin.login.post'), [
            'email'    => 'expired-admin@example.com',
            'password' => 'Secret123!',
        ])->assertRedirect(route('admin.org.billing'));

        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_seller_cannot_use_crm_when_subscription_expired(): void
    {
        $tenant = $this->makeVerifiedTenant();
        $seller = $this->createSellerUser(null, [
            'tenant_id' => $tenant->id,
        ]);

        $this->mock(SubscriptionAccessService::class, function ($mock) {
            $mock->shouldReceive('canUseCrm')->andReturn(false);
            $mock->shouldReceive('canAccessOrgBilling')->andReturn(true);
        });

        $this->actingAs($seller, 'seller')
            ->get(route('admin.index.get'))
            ->assertRedirect(route('admin.login.get'));

        $this->assertGuest('seller');
    }

    private function makeVerifiedTenant(): Tenant
    {
        return Tenant::query()->create([
            'name'              => 'Expired Co',
            'slug'              => 'expired-co-'.uniqid(),
            'email'             => 'expired-'.uniqid().'@example.com',
            'password'          => Hash::make('password'),
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function mockExpiredOrgBillingAccess(): void
    {
        $this->mock(SubscriptionAccessService::class, function ($mock) {
            $mock->shouldReceive('canUseCrm')->andReturn(false);
            $mock->shouldReceive('canAccessOrgBilling')->andReturn(true);
        });
    }

    private function ensureTenantBillingColumns(): void
    {
        if (! Schema::connection('central')->hasColumn('tenants', 'email_verified_at')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->timestamp('email_verified_at')->nullable();
                $table->string('preferred_billing_currency', 3)->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->boolean('trial_used')->default(false);
            });
        }
    }
}
