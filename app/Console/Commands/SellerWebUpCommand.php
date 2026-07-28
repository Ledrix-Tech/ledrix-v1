<?php

namespace App\Console\Commands;

class SellerWebUpCommand extends SectionWebMaintenanceCommand
{
    protected $signature = 'seller-web-up';

    protected $description = 'Bring the seller portal back online';

    protected function sectionKey(): string
    {
        return 'seller';
    }

    protected function puttingDown(): bool
    {
        return false;
    }
}
