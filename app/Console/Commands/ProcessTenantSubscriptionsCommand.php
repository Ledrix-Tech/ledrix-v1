<?php

namespace App\Console\Commands;

use App\Services\Tenant\ProcessTenantSubscriptionsService;
use Illuminate\Console\Command;

class ProcessTenantSubscriptionsCommand extends Command
{
    protected $signature = 'tenants:process-subscriptions';

    protected $description = 'Send paid subscription renewal reminders and expire overdue memberships';

    public function handle(ProcessTenantSubscriptionsService $service): int
    {
        $stats = $service->run();

        $this->info('7-day renewal reminders sent: ' . $stats['reminders_7d']);
        $this->info('3-day renewal reminders sent: ' . ($stats['reminders_3d'] ?? 0));
        $this->info('1-day renewal reminders sent: ' . $stats['reminders_1d']);
        $this->info('Active memberships marked past due: ' . $stats['marked_past_due']);
        $this->info('Past-due memberships expired: ' . $stats['expired']);

        return self::SUCCESS;
    }
}
