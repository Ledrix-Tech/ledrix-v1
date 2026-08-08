<?php

namespace Tests\Feature;

use App\Models\Central\PackagePricing;
use App\Models\Central\SystemAnnouncement;
use App\Models\Central\Tenant;
use App\Models\Central\TenantAnnouncementDismissal;
use App\Models\Central\TenantMembership;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesPortalUsers;
use Tests\Support\MigratesUpworkForTests;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

/**
 * A-02–A-04: Admin dashboard shows SaaS health, announcements, usage.
 *
 * @group admin
 */
class AdminSaasDashboardTest extends TestCase
{
    use CreatesPortalUsers;
    use MigratesUpworkForTests;
    use RefreshDatabase;
    use UsesSqliteCentral;

    protected function afterRefreshingDatabase(): void
    {
        $this->migrateUpworkTables();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->ensureSaasCentralTables();
        $this->mockTenantFeaturesEnabled();
    }

    public function test_admin_dashboard_shows_trial_banner_usage_and_announcement(): void
    {
        [$tenant, $admin] = $this->seedTrialTenantWithAnnouncement();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.index.get'))
            ->assertOk()
            ->assertSee('Free trial active', false)
            ->assertSee('Plan usage', false)
            ->assertSee('SaaS dashboard notice', false)
            ->assertSee($tenant->plan->name, false);
    }

    public function test_admin_can_dismiss_announcement_from_crm(): void
    {
        [$tenant, $admin, $announcement] = $this->seedTrialTenantWithAnnouncement();

        $this->actingAs($admin, 'admin')
            ->post(route('admin.org.announcements.dismiss', $announcement->id))
            ->assertRedirect();

        $this->assertTrue(
            TenantAnnouncementDismissal::query()
                ->where('tenant_id', $tenant->id)
                ->where('announcement_id', $announcement->id)
                ->exists()
        );

        $this->actingAs($admin, 'admin')
            ->get(route('admin.index.get'))
            ->assertOk()
            ->assertDontSee('SaaS dashboard notice', false);
    }

    public function test_seller_inactive_subscription_message_points_to_admin_billing(): void
    {
        $tenant = $this->makeTenant(onTrial: false, expired: true);
        $seller = $this->createSellerUser(null, ['tenant_id' => $tenant->id]);

        $this->actingAs($seller, 'seller')
            ->get(route('admin.index.get'))
            ->assertRedirect(route('admin.login.get'))
            ->assertSessionHas('error', function (string $message) {
                return str_contains($message, 'Ask your administrator to renew billing');
            });
    }

    /**
     * @return array{0: Tenant, 1: \App\Models\Admin, 2: SystemAnnouncement}
     */
    private function seedTrialTenantWithAnnouncement(): array
    {
        $tenant = $this->makeTenant(onTrial: true, expired: false);
        $admin = $this->createAdmin([
            'role'      => 'admin',
            'tenant_id' => $tenant->id,
        ]);

        $announcement = SystemAnnouncement::query()->create([
            'title'          => 'SaaS dashboard notice',
            'message'        => 'Visible inside Admin CRM.',
            'type'           => 'info',
            'target'         => 'all',
            'is_dismissible' => true,
            'status'         => 'active',
        ]);

        return [$tenant, $admin, $announcement];
    }

    private function makeTenant(bool $onTrial, bool $expired): Tenant
    {
        $plan = PackagePricing::query()->create([
            'name'          => 'Growth',
            'slug'          => 'growth-'.uniqid(),
            'monthly_price' => 49,
            'yearly_price'  => 490,
            'currency'      => 'USD',
            'trial_days'    => 14,
            'is_popular'    => true,
            'is_public'     => true,
            'sort_order'    => 1,
            'status'        => 'active',
            'max_brands'    => 5,
            'max_sellers'   => 10,
            'max_admins'    => 3,
            'max_clients'   => 50,
            'max_leads_per_month' => 500,
            'max_orders'    => 200,
        ]);

        $tenant = Tenant::query()->create([
            'plan_id'           => $plan->id,
            'name'              => 'Acme CRM',
            'slug'              => 'acme-'.uniqid(),
            'email'             => 'acme-'.uniqid().'@example.com',
            'password'          => Hash::make('password'),
            'status'            => 'active',
            'email_verified_at' => now(),
            'trial_ends_at'     => $onTrial ? now()->addDays(10) : now()->subDay(),
            'trial_used'        => true,
        ]);

        TenantMembership::query()->create([
            'tenant_id'     => $tenant->id,
            'plan_id'       => $plan->id,
            'billing_cycle' => 'monthly',
            'amount'        => 49,
            'currency'      => 'USD',
            'api_key'       => 'key_'.uniqid(),
            'start_date'    => now()->subDays(5)->toDateString(),
            'end_date'      => $expired ? now()->subDay()->toDateString() : now()->addDays(25)->toDateString(),
            'trial_start'   => $onTrial ? now()->subDays(5)->toDateString() : null,
            'trial_end'     => $onTrial ? now()->addDays(10)->toDateString() : null,
            'status'        => $expired ? 'expired' : ($onTrial ? 'trialing' : 'active'),
            'renewed_by'    => 'tenant',
        ]);

        return $tenant->fresh(['plan']);
    }

    private function ensureSaasCentralTables(): void
    {
        if (! Schema::connection('central')->hasColumn('tenants', 'email_verified_at')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->boolean('trial_used')->default(false);
            });
        }

        if (! Schema::connection('central')->hasColumn('package_pricings', 'max_brands')) {
            Schema::connection('central')->table('package_pricings', function (Blueprint $table) {
                $table->integer('max_brands')->nullable();
                $table->integer('max_sellers')->nullable();
                $table->integer('max_admins')->nullable();
                $table->integer('max_clients')->nullable();
                $table->integer('max_leads_per_month')->nullable();
                $table->integer('max_orders')->nullable();
            });
        }

        if (! Schema::connection('central')->hasTable('tenant_memberships')) {
            Schema::connection('central')->create('tenant_memberships', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('plan_id');
                $table->string('billing_cycle')->default('monthly');
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('currency', 3)->default('USD');
                $table->string('api_key', 64)->unique();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->date('trial_start')->nullable();
                $table->date('trial_end')->nullable();
                $table->string('renewed_by')->default('tenant');
                $table->string('status')->default('trialing');
                $table->string('conversion_source')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('tenant_usage_snapshots')) {
            Schema::connection('central')->create('tenant_usage_snapshots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->unique();
                $table->unsignedInteger('total_brands')->default(0);
                $table->unsignedInteger('total_sellers')->default(0);
                $table->unsignedInteger('total_admins')->default(0);
                $table->unsignedInteger('total_clients')->default(0);
                $table->unsignedInteger('total_orders')->default(0);
                $table->unsignedInteger('total_payment_links')->default(0);
                $table->unsignedInteger('total_account_keys')->default(0);
                $table->unsignedInteger('total_projects')->default(0);
                $table->unsignedInteger('leads_this_month')->default(0);
                $table->timestamp('month_reset_at')->nullable();
                $table->unsignedInteger('storage_used_mb')->default(0);
                $table->timestamp('last_synced_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('system_announcements')) {
            Schema::connection('central')->create('system_announcements', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->longText('message');
                $table->string('type')->default('info');
                $table->string('target', 80)->default('all');
                $table->boolean('is_dismissible')->default(true);
                $table->timestamp('show_from')->nullable();
                $table->timestamp('show_until')->nullable();
                $table->string('status')->default('active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('tenant_announcement_dismissals')) {
            Schema::connection('central')->create('tenant_announcement_dismissals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('announcement_id');
                $table->timestamp('dismissed_at')->nullable();
            });
        }
    }
}
