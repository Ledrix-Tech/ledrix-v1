<?php

namespace Tests\Feature;

use App\Models\Central\AuditLog;
use App\Models\Central\PackagePricing;
use App\Models\Central\SuperAdmin;
use App\Models\Central\Tenant;
use App\Models\Central\TenantDataExportRequest;
use App\Models\Central\TenantMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesPortalUsers;
use Tests\Support\MigratesUpworkForTests;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class TenantDataExportTest extends TestCase
{
    use CreatesPortalUsers;
    use MigratesUpworkForTests;
    use RefreshDatabase;
    use UsesSqliteCentral;

    protected function afterRefreshingDatabase(): void
    {
        $this->migrateUpworkTables();
    }

    protected function beforeRefreshingDatabase(): void
    {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
        } catch (\Throwable) {
            $this->markTestSkipped('Primary database is not available in this environment.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->mockTenantFeaturesEnabled();
        $this->mockCrmWorkspaceAccess();
        Mail::fake();
    }

    public function test_admin_can_request_workspace_export(): void
    {
        [$tenant, $admin] = $this->seedTenantAdmin();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.org.data-export'))
            ->assertOk()
            ->assertSee('Workspace data export', false);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.org.data-export.store'), [
                'reason' => 'Moving agencies and need a copy of our CRM rows.',
            ])
            ->assertRedirect(route('admin.org.data-export'));

        $row = TenantDataExportRequest::query()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($row);
        $this->assertSame('pending', $row->status);

        $this->assertTrue(
            AuditLog::query()->where('action', 'tenant.data_export_requested')->exists()
        );
    }

    public function test_super_admin_can_generate_and_download_zip(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('zip extension required');
        }

        $tenant = Tenant::query()->create([
            'name'     => 'Export Co',
            'slug'     => 'export-co',
            'email'    => 'export@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);

        $sa = SuperAdmin::query()->create([
            'name'     => 'Admin',
            'email'    => 'sa-export@example.com',
            'password' => Hash::make('Password1!'),
            'role'     => 'admin',
            'status'   => 'active',
        ]);

        $this->actingAs($sa, 'super_admin')
            ->post(route('super-admin.tenant.data-export.generate', $tenant->id))
            ->assertRedirect();

        $export = TenantDataExportRequest::query()->where('tenant_id', $tenant->id)->first();
        $this->assertNotNull($export);
        $this->assertSame('ready', $export->status);
        $this->assertNotEmpty($export->file_path);
        $this->assertTrue(Storage::disk('local')->exists($export->file_path));

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path($export->file_path)) === true);
        $readme = $zip->getFromName('README.txt');
        $zip->close();
        $this->assertIsString($readme);
        $this->assertStringContainsString('NOT a MySQL dump', $readme);
        $this->assertStringNotContainsString($tenant->password, $readme);

        $this->actingAs($sa, 'super_admin')
            ->get(route('super-admin.data-exports.download', $export->id))
            ->assertOk();
    }

    /**
     * @return array{0: Tenant, 1: \App\Models\Admin}
     */
    private function seedTenantAdmin(): array
    {
        $plan = PackagePricing::query()->create([
            'name'          => 'Agency',
            'slug'          => 'agency-export-'.uniqid(),
            'monthly_price' => 99,
            'yearly_price'  => 990,
            'currency'      => 'USD',
            'trial_days'    => 14,
            'is_popular'    => true,
            'is_public'     => true,
            'sort_order'    => 1,
            'status'        => 'active',
        ]);

        $tenant = Tenant::query()->create([
            'plan_id'  => $plan->id,
            'name'     => 'Org Export',
            'slug'     => 'org-export-'.uniqid(),
            'email'    => 'org-export-'.uniqid().'@example.com',
            'password' => Hash::make('password'),
            'status'   => 'active',
        ]);

        if (\Illuminate\Support\Facades\Schema::connection('central')->hasTable('tenant_memberships')) {
            TenantMembership::query()->create([
                'tenant_id'     => $tenant->id,
                'plan_id'       => $plan->id,
                'billing_cycle' => 'monthly',
                'amount'        => 99,
                'currency'      => 'USD',
                'api_key'       => 'key_'.uniqid(),
                'start_date'    => now()->toDateString(),
                'end_date'      => now()->addMonth()->toDateString(),
                'status'        => 'active',
                'renewed_by'    => 'tenant',
            ]);
        }

        $admin = $this->createAdmin([
            'role'      => 'admin',
            'tenant_id' => $tenant->id,
            'email'     => 'owner-export-'.uniqid().'@example.com',
            'password'  => 'password',
        ]);

        return [$tenant, $admin];
    }
}
