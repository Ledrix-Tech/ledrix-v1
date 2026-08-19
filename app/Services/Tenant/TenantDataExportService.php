<?php

namespace App\Services\Tenant;

use App\Models\Central\Tenant;
use App\Models\Central\TenantDataExportRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class TenantDataExportService
{
    public const TENANT_LINK_HOURS = 48;

    public const SA_RETAIN_DAYS = 14;

    /** CRM tables (primary DB) scoped by tenant_id. */
    private const CRM_TABLES = [
        'leads',
        'clients',
        'orders',
        'payments',
        'payment_links',
        'brands',
        'sellers',
        'admins',
        'account_keys',
        'client_tickets',
        'projects',
        'project_tasks',
        'lead_assignments',
        'questionnairs',
        'questionnaires',
        'briefs',
        'upwork_orders',
        'upwork_clients',
        'upwork_payments',
        'upwork_payment_links',
    ];

    /** Central tables scoped by tenant_id. */
    private const CENTRAL_TABLES = [
        'tenant_memberships',
        'tenant_invoices',
        'tenant_payments',
        'tenant_limits',
        'tenant_limit_overrides',
        'tenant_feature_overrides',
        'tenant_usage_snapshots',
        'tenant_api_tokens',
        'audit_logs',
        'platform_support_tickets',
        'tenant_announcement_dismissals',
        'tenant_renewal_requests',
    ];

    private const STRIP_COLUMNS = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'paypal_secret',
        'paypal_webhook_id',
        'plain_token',
        'token',
    ];

    public function generate(TenantDataExportRequest $export): void
    {
        $export->refresh();
        $tenant = Tenant::query()->findOrFail($export->tenant_id);

        $export->forceFill(['status' => 'processing'])->save();

        $disk = Storage::disk('local');
        $relativeDir = 'tenant-exports/'.$tenant->id;
        $disk->makeDirectory($relativeDir);

        $filename = 'ledrix-workspace-'.$tenant->id.'-'.$export->id.'-'.now()->format('Ymd_His').'.zip';
        $relativePath = $relativeDir.'/'.$filename;
        $absoluteZip = $disk->path($relativePath);

        $tmp = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ledrix-export-'.$export->id.'-'.uniqid();
        if (! mkdir($tmp, 0700, true) && ! is_dir($tmp)) {
            throw new RuntimeException('Could not create export temp directory.');
        }

        try {
            $manifest = [];
            $manifest[] = $this->writeReadme($tmp, $tenant, $export);

            foreach (self::CRM_TABLES as $table) {
                $count = $this->dumpTable('primary', $table, $tenant->id, $tmp.'/crm');
                if ($count !== null) {
                    $manifest[] = "crm/{$table}.csv\t{$count} rows";
                }
            }

            $this->dumpCentralTenantRow($tenant, $tmp.'/saas');
            $manifest[] = "saas/tenant.csv\t1 row";

            foreach (self::CENTRAL_TABLES as $table) {
                $count = $this->dumpTable('central', $table, $tenant->id, $tmp.'/saas');
                if ($count !== null) {
                    $manifest[] = "saas/{$table}.csv\t{$count} rows";
                }
            }

            $referralCount = $this->dumpReferrals($tenant->id, $tmp.'/saas');
            if ($referralCount !== null) {
                $manifest[] = "saas/referrals.csv\t{$referralCount} rows";
            }

            file_put_contents($tmp.'/MANIFEST.txt', implode("\n", $manifest)."\n");

            $this->zipDirectory($tmp, $absoluteZip);
        } finally {
            $this->deleteDirectory($tmp);
        }

        if (! is_file($absoluteZip)) {
            throw new RuntimeException('Export zip was not created.');
        }

        $export->forceFill([
            'status'     => 'ready',
            'file_path'  => $relativePath,
            'file_size'  => filesize($absoluteZip) ?: 0,
            'ready_at'   => now(),
            'expires_at' => now()->addHours(self::TENANT_LINK_HOURS),
            'meta'       => array_merge($export->meta ?? [], [
                'files' => $manifest,
            ]),
        ])->save();
    }

    private function dumpTable(string $connection, string $table, int $tenantId, string $dir): ?int
    {
        if (! Schema::connection($connection)->hasTable($table)) {
            return null;
        }

        if (! Schema::connection($connection)->hasColumn($table, 'tenant_id')) {
            return null;
        }

        if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {
            throw new RuntimeException("Could not create {$dir}");
        }

        $columns = array_values(array_filter(
            Schema::connection($connection)->getColumnListing($table),
            fn (string $col) => ! in_array($col, self::STRIP_COLUMNS, true)
        ));

        $path = $dir.'/'.$table.'.csv';
        $handle = fopen($path, 'w');
        if ($handle === false) {
            throw new RuntimeException("Could not write {$path}");
        }

        fputcsv($handle, $columns);
        $count = 0;

        $query = DB::connection($connection)
            ->table($table)
            ->where('tenant_id', $tenantId);

        if (Schema::connection($connection)->hasColumn($table, 'id')) {
            $query->orderBy('id');
        }

        $query->chunk(500, function ($rows) use ($handle, $columns, &$count) {
                foreach ($rows as $row) {
                    $line = [];
                    $arr = (array) $row;
                    foreach ($columns as $col) {
                        $value = $arr[$col] ?? '';
                        if (is_array($value) || is_object($value)) {
                            $value = json_encode($value);
                        }
                        $line[] = $value;
                    }
                    fputcsv($handle, $line);
                    $count++;
                }
            });

        fclose($handle);

        return $count;
    }

    private function dumpCentralTenantRow(Tenant $tenant, string $dir): void
    {
        if (! is_dir($dir) && ! mkdir($dir, 0700, true) && ! is_dir($dir)) {
            throw new RuntimeException("Could not create {$dir}");
        }

        $row = $tenant->toArray();
        unset(
            $row['password'],
            $row['remember_token'],
            $row['two_factor_secret'],
            $row['two_factor_recovery_codes'],
            $row['jazzcash_payment_token'],
            $row['stripe_payment_method_id'],
            $row['stripe_setup_intent_id'],
        );

        $path = $dir.'/tenant.csv';
        $handle = fopen($path, 'w');
        fputcsv($handle, array_keys($row));
        $values = array_map(function ($value) {
            if (is_array($value) || is_object($value)) {
                return json_encode($value);
            }

            return $value;
        }, array_values($row));
        fputcsv($handle, $values);
        fclose($handle);
    }

    private function dumpReferrals(int $tenantId, string $dir): ?int
    {
        if (! Schema::connection('central')->hasTable('referrals')) {
            return null;
        }

        $columns = Schema::connection('central')->getColumnListing('referrals');
        $path = $dir.'/referrals.csv';
        $handle = fopen($path, 'w');
        fputcsv($handle, $columns);
        $count = 0;

        DB::connection('central')
            ->table('referrals')
            ->where(function ($query) use ($tenantId) {
                $query->where('referrer_tenant_id', $tenantId)
                    ->orWhere('referred_tenant_id', $tenantId);
            })
            ->orderBy('id')
            ->chunk(200, function ($rows) use ($handle, $columns, &$count) {
                foreach ($rows as $row) {
                    $arr = (array) $row;
                    $line = [];
                    foreach ($columns as $col) {
                        $line[] = $arr[$col] ?? '';
                    }
                    fputcsv($handle, $line);
                    $count++;
                }
            });

        fclose($handle);

        return $count;
    }

    private function writeReadme(string $tmp, Tenant $tenant, TenantDataExportRequest $export): string
    {
        $generatedAt = now()->utc()->toDateTimeString();
        $body = <<<TXT
Ledrix workspace data package
=============================
This is NOT a MySQL dump of the platform database.

Tenant: {$tenant->name} (ID {$tenant->id}, {$tenant->slug})
Export request: #{$export->id}
Generated (UTC): {$generatedAt}

Contents:
- crm/*.csv   Tenant CRM rows (leads, clients, orders, …)
- saas/*.csv  Subscription / invoice / audit rows for this tenant

Secrets (payment keys, passwords, 2FA) are omitted.
TXT;
        file_put_contents($tmp.'/README.txt', $body);

        return 'README.txt';
    }

    private function zipDirectory(string $source, string $destination): void
    {
        $zip = new ZipArchive;
        if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create zip archive.');
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (! $file->isFile()) {
                continue;
            }
            $local = substr($file->getPathname(), strlen($source) + 1);
            $zip->addFile($file->getPathname(), str_replace('\\', '/', $local));
        }

        $zip->close();
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            /** @var \SplFileInfo $file */
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        @rmdir($dir);
    }

    public function purgeExpired(): int
    {
        $cutoff = now()->subDays(self::SA_RETAIN_DAYS);
        $rows = TenantDataExportRequest::query()
            ->where('status', 'ready')
            ->whereNotNull('file_path')
            ->where('ready_at', '<', $cutoff)
            ->get();

        $n = 0;
        foreach ($rows as $row) {
            if ($row->file_path) {
                Storage::disk('local')->delete($row->file_path);
            }
            $row->forceFill([
                'status'    => 'expired',
                'file_path' => null,
            ])->save();
            $n++;
        }

        return $n;
    }
}
