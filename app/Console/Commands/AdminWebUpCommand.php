<?php

namespace App\Console\Commands;

class AdminWebUpCommand extends SectionWebMaintenanceCommand
{
    protected $signature = 'admin-web-up';

    protected $description = 'Bring the admin panel back online';

    protected function sectionKey(): string
    {
        return 'admin';
    }

    protected function puttingDown(): bool
    {
        return false;
    }
}
