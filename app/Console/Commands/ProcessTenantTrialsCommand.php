<?php

namespace App\Console\Commands;

use App\Services\Tenant\ProcessTenantTrialsService;
use Illuminate\Console\Command;

class ProcessTenantTrialsCommand extends Command
{
    protected $signature = 'tenants:process-trials';

    protected $description = 'Send trial reminders, issue Payoneer invoices, and expire unpaid subscriptions';

    public function handle(ProcessTenantTrialsService $service): int
    {
        $stats = $service->run();

        $this->info('Trial reminders sent: ' . $stats['reminders_sent']);
        $this->info('Trials marked past due: ' . $stats['trials_ended']);
        $this->info('Memberships expired: ' . $stats['expired']);

        return self::SUCCESS;
    }
}
