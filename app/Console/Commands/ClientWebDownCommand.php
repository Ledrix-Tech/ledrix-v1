<?php

namespace App\Console\Commands;

class ClientWebDownCommand extends SectionWebMaintenanceCommand
{
    protected $signature = 'client-web-down {--message= : Optional message shown on the maintenance page}';

    protected $description = 'Put the client portal in maintenance mode';

    protected function sectionKey(): string
    {
        return 'client';
    }

    protected function puttingDown(): bool
    {
        return true;
    }
}
