<?php

namespace App\Console\Commands;

class AdminWebDownCommand extends SectionWebMaintenanceCommand
{
    protected $signature = 'admin-web-down {--message= : Optional message shown on the maintenance page}';

    protected $description = 'Put the admin panel in maintenance mode';

    protected function sectionKey(): string
    {
        return 'admin';
    }

    protected function puttingDown(): bool
    {
        return true;
    }
}
