<?php

namespace App\Console\Commands;

class FrontWebDownCommand extends SectionWebMaintenanceCommand
{
    protected $signature = 'front-web-down {--message= : Optional message shown on the maintenance page}';

    protected $description = 'Put the public website in maintenance mode';

    protected function sectionKey(): string
    {
        return 'front';
    }

    protected function puttingDown(): bool
    {
        return true;
    }
}
