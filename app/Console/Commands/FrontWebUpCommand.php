<?php

namespace App\Console\Commands;

class FrontWebUpCommand extends SectionWebMaintenanceCommand
{
    protected $signature = 'front-web-up';

    protected $description = 'Bring the public website back online';

    protected function sectionKey(): string
    {
        return 'front';
    }

    protected function puttingDown(): bool
    {
        return false;
    }
}
