<?php

namespace App\Console\Commands;

class SellerWebDownCommand extends SectionWebMaintenanceCommand
{
    protected $signature = 'seller-web-down {--message= : Optional message shown on the maintenance page}';

    protected $description = 'Put the seller portal in maintenance mode';

    protected function sectionKey(): string
    {
        return 'seller';
    }

    protected function puttingDown(): bool
    {
        return true;
    }
}
