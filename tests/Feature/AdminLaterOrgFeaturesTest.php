<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Central\AuditLog;
use App\Models\Central\PackagePricing;
use App\Models\Central\Tenant;
use App\Models\Central\TenantMembership;
use App\Services\Security\TotpService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesPortalUsers;
use Tests\Support\MigratesUpworkForTests;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

/**
 * A-16–A-18: Admin 2FA, custom domain self-serve, workspace audit log.
 *
 * @group admin
 */
class AdminLaterOrgFeaturesTest extends TestCase
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
        $this->ensureLaterOrgTables();
        $this->mockTenantFeaturesEnabled();
        $this->mockCrmWorkspaceAccess();
    }

    public function test_admin_with_2fa_is_challenged_on_login(): void
    {
        $this->ensureAdminTwoFactorColumns();
        [, $admin] = $this->seedTenantAdmin();

        $totp = app(TotpService::class);
        $secret = $totp->generateSecret();
        $admin->forceFill([
            'two_factor_secret'         => $secret,
            'two_factor_recovery_codes' => json_encode($totp->hashRecoveryCodes(['AAAA-BBBB'])),
        ])->save();

        $this->post(route('admin.login.post'), [
            'email'    => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.2fa.challenge'));

        $this->assertGuest('admin');
        $this->assertEquals($admin->id, session('admin_2fa_pending_id'));

        $code = $this->currentTotp($secret);
        $this->post(route('admin.2fa.challenge.post'), ['code' => $code])
            ->assertRedirect(route('admin.index.get'));

        $this->assertAuthenticatedAs($admin, 'admin');
    }

    public function test_admin_can_view_workspace_audit_logs(): void
    {
        [$tenant, $admin] = $this->seedTenantAdmin();

        AuditLog::record(
            'tenant.settings_updated',
            (int) $tenant->id,
            'admin',
            $admin->id,
            $admin->name,
            ['description' => 'Settings saved for test']
        );

        $this->actingAs($admin, 'admin')
            ->get(route('admin.org.audit-logs'))
            ->assertOk()
            ->assertSee('Workspace audit log', false)
            ->assertSee('Settings saved for test', false);
    }

    public function test_admin_can_set_and_verify_custom_domain_in_testing(): void
    {
        [$tenant, $admin] = $this->seedTenantAdmin();
        $this->enablePlanFeatures($tenant, ['feature_custom_domain' => true, 'feature_white_label' => true]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.org.domain.update'), [
                'custom_domain' => 'crm.agency-test.com',
            ])
            ->assertRedirect(route('admin.org.domain'));

        $tenant->refresh();
        $this->assertSame('crm.agency-test.com', $tenant->custom_domain);
        $this->assertFalse((bool) $tenant->custom_domain_verified);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.org.domain.verify'))
            ->assertRedirect(route('admin.org.domain'));

        $this->assertTrue((bool) $tenant->fresh()->custom_domain_verified);
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
            'feature_custom_domain' => false,
            'feature_white_label'   => false,
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
            'status'        => 'active',
            'renewed_by'    => 'tenant',
        ]);

        $admin = $this->createAdmin([
            'role'      => 'admin',
            'tenant_id' => $tenant->id,
            'email'     => 'owner-'.uniqid().'@example.com',
            'password'  => 'password',
        ]);

        return [$tenant->fresh(['plan']), $admin];
    }

    private function enablePlanFeatures(Tenant $tenant, array $features): void
    {
        $tenant->plan?->forceFill($features)->save();
    }

    private function currentTotp(string $secret): string
    {
        $service = app(TotpService::class);
        $ref = new \ReflectionClass($service);
        $method = $ref->getMethod('at');
        $method->setAccessible(true);
        $slice = (int) floor(time() / 30);

        return $method->invoke($service, $secret, $slice);
    }

    private function ensureAdminTwoFactorColumns(): void
    {
        if (! Schema::hasColumn('admins', 'two_factor_secret')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->text('two_factor_secret')->nullable();
                $table->text('two_factor_recovery_codes')->nullable();
            });
        }
    }

    private function ensureLaterOrgTables(): void
    {
        if (! Schema::connection('central')->hasColumn('tenants', 'email_verified_at')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->timestamp('email_verified_at')->nullable();
                $table->timestamp('trial_ends_at')->nullable();
                $table->boolean('trial_used')->default(false);
            });
        }

        foreach (['custom_domain', 'logo'] as $column) {
            if (! Schema::connection('central')->hasColumn('tenants', $column)) {
                Schema::connection('central')->table('tenants', function (Blueprint $table) use ($column) {
                    if ($column === 'custom_domain') {
                        $table->string('custom_domain')->nullable();
                        $table->boolean('custom_domain_verified')->default(false);
                    } else {
                        $table->string('logo')->nullable();
                    }
                });
            }
        }

        if (! Schema::connection('central')->hasColumn('tenants', 'custom_domain_verified')
            && Schema::connection('central')->hasColumn('tenants', 'custom_domain')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->boolean('custom_domain_verified')->default(false);
            });
        }

        if (! Schema::connection('central')->hasColumn('tenants', 'meta')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->json('meta')->nullable();
            });
        }

        foreach (['feature_custom_domain', 'feature_white_label', 'max_admins'] as $column) {
            if (! Schema::connection('central')->hasColumn('package_pricings', $column)) {
                Schema::connection('central')->table('package_pricings', function (Blueprint $table) use ($column) {
                    if ($column === 'max_admins') {
                        $table->integer($column)->nullable();
                    } else {
                        $table->boolean($column)->default(false);
                    }
                });
            }
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
                $table->timestamps();
            });
        }

        if (! Schema::connection('central')->hasTable('tenant_feature_overrides')) {
            Schema::connection('central')->create('tenant_feature_overrides', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->unique();
                $table->boolean('feature_custom_domain')->nullable();
                $table->boolean('feature_white_label')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        $this->ensureAdminTwoFactorColumns();
    }
}
