<?php

namespace App\Console\Commands;

use App\Services\Tenant\TenantDataExportService;
use Illuminate\Console\Command;

class PurgeTenantDataExportsCommand extends Command
{
    protected $signature = 'tenants:purge-data-exports';

    protected $description = 'Delete workspace export ZIP files older than Super Admin retention';

    public function handle(TenantDataExportService $exports): int
    {
        $n = $exports->purgeExpired();
        $this->info("Purged {$n} expired workspace export(s).");

        return self::SUCCESS;
    }
}
