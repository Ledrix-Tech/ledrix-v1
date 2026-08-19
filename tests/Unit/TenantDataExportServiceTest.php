<?php

namespace Tests\Unit;

use App\Models\Central\Tenant;
use App\Models\Central\TenantDataExportRequest;
use App\Services\Tenant\TenantDataExportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UsesSqliteCentral;
use Tests\TestCase;

class TenantDataExportServiceTest extends TestCase
{
    use UsesSqliteCentral;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootSqliteCentral();
        $this->bootSqlitePrimary();
    }

    public function test_zip_contains_readme_and_omits_password(): void
    {
        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('zip extension required');
        }

        $tenant = Tenant::query()->create([
            'name'     => 'Export Co',
            'slug'     => 'export-co',
            'email'    => 'export@example.com',
            'password' => Hash::make('secret-password'),
            'status'   => 'active',
        ]);

        DB::connection('primary')->table('leads')->insert([
            'tenant_id' => $tenant->id,
            'name'      => 'Ada',
            'email'     => 'ada@example.com',
        ]);

        $export = TenantDataExportRequest::query()->create([
            'tenant_id'         => $tenant->id,
            'requested_by_type' => 'super_admin',
            'reason'            => 'QA',
            'status'            => 'approved',
        ]);

        app(TenantDataExportService::class)->generate($export);

        $export->refresh();
        $this->assertSame('ready', $export->status);
        $this->assertTrue(Storage::disk('local')->exists($export->file_path));

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('local')->path($export->file_path)) === true);
        $readme = $zip->getFromName('README.txt');
        $tenantCsv = $zip->getFromName('saas/tenant.csv');
        $leadsCsv = $zip->getFromName('crm/leads.csv');
        $zip->close();

        $this->assertIsString($readme);
        $this->assertStringContainsString('NOT a MySQL dump', $readme);
        $this->assertIsString($tenantCsv);
        $this->assertStringNotContainsString('secret-password', $tenantCsv);
        $this->assertStringNotContainsString('password', explode("\n", $tenantCsv)[0]);
        $this->assertIsString($leadsCsv);
        $this->assertStringContainsString('Ada', $leadsCsv);

        Storage::disk('local')->delete($export->file_path);
    }

    public function test_purge_removes_old_files(): void
    {
        Storage::disk('local')->put('tenant-exports/1/old.zip', 'x');

        $export = TenantDataExportRequest::query()->create([
            'tenant_id'  => 1,
            'status'     => 'ready',
            'file_path'  => 'tenant-exports/1/old.zip',
            'ready_at'   => now()->subDays(20),
            'expires_at' => now()->subDays(18),
        ]);

        $n = app(TenantDataExportService::class)->purgeExpired();
        $this->assertSame(1, $n);
        $this->assertSame('expired', $export->fresh()->status);
        $this->assertFalse(Storage::disk('local')->exists('tenant-exports/1/old.zip'));
    }

    private function bootSqlitePrimary(): void
    {
        config([
            'database.connections.primary' => [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        app('db')->purge('primary');
        app('db')->reconnect('primary');

        Schema::connection('primary')->create('leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
    }
}
