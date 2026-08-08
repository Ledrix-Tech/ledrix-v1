<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use App\Models\Central\TenantApiToken;
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
 * A-05–A-15: Organization portal (overview, team, API tokens, plan, settings, billing controls).
 *
 * @group admin
 */
class AdminOrgFeaturesTest extends TestCase
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
        $this->ensureOrgCentralTables();
        $this->mockTenantFeaturesEnabled();
        $this->mockCrmWorkspaceAccess();
    }

    public function test_admin_can_open_organization_overview(): void
    {
        [$tenant, $admin] = $this->seedTenantAdmin();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.org.overview'))
            ->assertOk()
            ->assertSee('Organization overview', false)
            ->assertSee($tenant->name, false)
            ->assertSee('Plan usage', false)
            ->assertSee('Team seats', false);
    }

    public function test_admin_can_add_and_remove_finance_seat(): void
    {
        [$tenant, $admin] = $this->seedTenantAdmin();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.org.team'))
            ->assertOk()
            ->assertSee('Team seats', false);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.org.team.store'), [
                'name'     => 'Finance User',
                'email'    => 'finance-seat@example.com',
                'password' => 'Secret123!',
                'role'     => 'finance',
            ])
            ->assertRedirect(route('admin.org.team'));

        $finance = Admin::withoutGlobalScopes()
            ->where('email', 'finance-seat@example.com')
            ->first();
        $this->assertNotNull($finance);
        $this->assertSame('finance', $finance->role);
        $this->assertSame((int) $tenant->id, (int) $finance->tenant_id);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.org.team.destroy', $finance->id))
            ->assertRedirect(route('admin.org.team'));

        $this->assertNull(Admin::withoutGlobalScopes()->find($finance->id));
    }

    public function test_admin_cannot_remove_last_admin_seat(): void
    {
        [, $admin] = $this->seedTenantAdmin();

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.org.team.destroy', $admin->id))
            ->assertForbidden();
    }

    public function test_admin_can_create_and_revoke_api_token(): void
    {
        [$tenant, $admin] = $this->seedTenantAdmin();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.org.api-tokens'))
            ->assertOk()
            ->assertSee('API tokens', false);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.org.api-tokens.store'), [
                'name'      => 'CI Token',
                'abilities' => '*',
            ])
            ->assertRedirect(route('admin.org.api-tokens'))
            ->assertSessionHas('new_api_token');

        $token = TenantApiToken::query()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($token);
        $this->assertSame('active', $token->status);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.org.api-tokens.revoke', $token->id))
            ->assertRedirect(route('admin.org.api-tokens'));

        $this->assertSame('revoked', $token->fresh()->status);
    }

    public function test_admin_can_view_plan_matrix(): void
    {
        [, $admin] = $this->seedTenantAdmin();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.org.plan'))
            ->assertOk()
            ->assertSee('Feature matrix', false)
            ->assertSee('Agency', false);
    }

    public function test_admin_can_update_organization_settings(): void
    {
        [$tenant, $admin] = $this->seedTenantAdmin();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.org.settings'))
            ->assertOk()
            ->assertSee('Organization settings', false);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.org.settings.update'), [
                'name'          => 'Renamed Org',
                'phone'         => '+923001234567',
                'country'       => 'PK',
                'website'       => 'https://example.com',
                'billing_name'  => 'Billing Desk',
                'billing_email' => 'billing@example.com',
                'billing_phone' => '+923009998887',
                'billing_address' => 'Karachi',
            ])
            ->assertRedirect(route('admin.org.settings'));

        $tenant->refresh();
        $this->assertSame('Renamed Org', $tenant->name);
        $this->assertSame('PK', $tenant->country);
        $this->assertSame('billing@example.com', $tenant->billing_email);
    }

    public function test_admin_can_toggle_auto_renew_and_cancel_at_period_end(): void
    {
        [$tenant, $admin] = $this->seedTenantAdmin();

        $membership = TenantMembership::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $membership->forceFill([
            'status'       => 'active',
            'end_date'     => now()->addYear()->toDateString(),
            'cancelled_at' => null,
            'cancel_reason'=> null,
        ])->save();
        $tenant->forceFill(['auto_renew' => false])->save();

        $this->assertFalse($membership->fresh()->isExpired());

        $this->actingAs($admin, 'admin')
            ->post(route('admin.org.billing.auto-renew'), ['auto_renew' => '1'])
            ->assertRedirect(route('admin.org.billing'));

        $this->assertTrue((bool) $tenant->fresh()->auto_renew);

        $this->actingAs($admin, 'admin')
            ->from(route('admin.org.billing'))
            ->post(route('admin.org.billing.cancel'), ['reason' => 'Switching plans'])
            ->assertRedirect(route('admin.org.billing'));

        $tenant->refresh();
        $membership->refresh();
        $this->assertFalse((bool) $tenant->auto_renew);
        $this->assertNotNull($membership->cancelled_at);
    }

    public function test_jazzcash_checkout_route_is_registered_for_admin_org(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.org.billing.jazzcash.checkout'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('tenant.billing.jazzcash.checkout'));
    }

    /**
     * @return array{0: Tenant, 1: Admin}
     */
    private function seedTenantAdmin(): array
    {
        $plan = PackagePricing::query()->create([
            'name'          => 'Agency',
            'slug'          => 'agency-'.uniqid(),
            'monthly_price' => 99,
            'yearly_price'  => 990,
            'currency'      => 'USD',
            'trial_days'    => 14,
            'is_popular'    => true,
            'is_public'     => true,
            'sort_order'    => 1,
            'status'        => 'active',
            'max_admins'    => 5,
        ]);

        $tenant = Tenant::query()->create([
            'plan_id'           => $plan->id,
            'name'              => 'Org Co',
            'slug'              => 'org-'.uniqid(),
            'email'             => 'org-'.uniqid().'@example.com',
            'password'          => Hash::make('password'),
            'status'            => 'active',
            'email_verified_at' => now(),
            'trial_ends_at'     => now()->addDays(7),
            'trial_used'        => true,
        ]);

        TenantMembership::query()->create([
            'tenant_id'     => $tenant->id,
            'plan_id'       => $plan->id,
            'billing_cycle' => 'monthly',
            'amount'        => 99,
            'currency'      => 'USD',
            'api_key'       => 'key_'.uniqid(),
            'start_date'    => now()->toDateString(),
            'end_date'      => now()->addMonth()->toDateString(),
            'trial_start'   => now()->toDateString(),
            'trial_end'     => now()->addDays(7)->toDateString(),
            'status'        => 'trialing',
            'renewed_by'    => 'tenant',
        ]);

        $admin = $this->createAdmin([
            'role'      => 'admin',
            'tenant_id' => $tenant->id,
            'email'     => 'owner-'.uniqid().'@example.com',
        ]);

        return [$tenant->fresh(['plan']), $admin];
    }

    private function ensureOrgCentralTables(): void
    {
        if (! Schema::connection('central')->hasColumn('tenants', 'email_verified_at')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->boolean('trial_used')->default(false);
            });
        }

        if (! Schema::connection('central')->hasColumn('package_pricings', 'max_admins')) {
            Schema::connection('central')->table('package_pricings', function (Blueprint $table) {
                $table->integer('max_admins')->nullable();
                $table->integer('max_brands')->nullable();
                $table->integer('max_sellers')->nullable();
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
                $table->timestamp('cancelled_at')->nullable();
                $table->string('cancel_reason')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        } elseif (! Schema::connection('central')->hasColumn('tenant_memberships', 'cancelled_at')) {
            Schema::connection('central')->table('tenant_memberships', function (Blueprint $table) {
                $table->timestamp('cancelled_at')->nullable();
                $table->string('cancel_reason')->nullable();
            });
        }

        if (! Schema::connection('central')->hasColumn('tenants', 'auto_renew')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->boolean('auto_renew')->default(false);
            });
        }

        foreach (['billing_name', 'billing_email', 'billing_phone', 'billing_address', 'phone', 'country', 'website'] as $column) {
            if (! Schema::connection('central')->hasColumn('tenants', $column)) {
                Schema::connection('central')->table('tenants', function (Blueprint $table) use ($column) {
                    match ($column) {
                        'billing_address' => $table->text($column)->nullable(),
                        'country' => $table->string($column, 5)->nullable(),
                        default => $table->string($column)->nullable(),
                    };
                });
            }
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

        if (! Schema::connection('central')->hasTable('tenant_invoices')) {
            Schema::connection('central')->create('tenant_invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('membership_id')->nullable();
                $table->unsignedBigInteger('payment_id')->nullable();
                $table->string('invoice_number')->nullable();
                $table->string('plan_name')->nullable();
                $table->string('billing_cycle')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('currency', 3)->default('USD');
                $table->decimal('tax_amount', 10, 2)->default(0);
                $table->decimal('total_amount', 10, 2)->default(0);
                $table->string('status')->default('issued');
                $table->string('pdf_path')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('due_at')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('tenant_limit_overrides')) {
            Schema::connection('central')->create('tenant_limit_overrides', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->unique();
                $table->integer('max_admins')->nullable();
                $table->integer('max_brands')->nullable();
                $table->integer('max_sellers')->nullable();
                $table->integer('max_clients')->nullable();
                $table->integer('max_orders')->nullable();
                $table->integer('max_leads_per_month')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('tenant_feature_overrides')) {
            Schema::connection('central')->create('tenant_feature_overrides', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->unique();
                $table->boolean('feature_ppc_module')->nullable();
                $table->boolean('feature_upwork_module')->nullable();
                $table->boolean('feature_milestone_payments')->nullable();
                $table->boolean('feature_stripe')->nullable();
                $table->boolean('feature_paypal')->nullable();
                $table->boolean('feature_webhooks')->nullable();
                $table->boolean('feature_chargeback_tracking')->nullable();
                $table->boolean('feature_dual_invoicing')->nullable();
                $table->boolean('feature_client_portal')->nullable();
                $table->boolean('feature_lead_prediction')->nullable();
                $table->boolean('feature_seller_leaderboard')->nullable();
                $table->boolean('feature_performance_bonus')->nullable();
                $table->boolean('feature_projects')->nullable();
                $table->boolean('feature_support_tickets')->nullable();
                $table->boolean('feature_api_access')->nullable();
                $table->boolean('feature_custom_domain')->nullable();
                $table->boolean('feature_white_label')->nullable();
                $table->text('override_reason')->nullable();
                $table->unsignedBigInteger('overridden_by')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }
    }
}
