<?php

namespace App\Console\Commands;

class ClientWebUpCommand extends SectionWebMaintenanceCommand
{
    protected $signature = 'client-web-up';

    protected $description = 'Bring the client portal back online';

    protected function sectionKey(): string
    {
        return 'client';
    }

    protected function puttingDown(): bool
    {
        return false;
    }
}
