<?php

namespace App\Console\Commands;

use App\Services\SectionMaintenanceService;
use Illuminate\Console\Command;

abstract class SectionWebMaintenanceCommand extends Command
{
    abstract protected function sectionKey(): string;

    abstract protected function puttingDown(): bool;

    public function handle(SectionMaintenanceService $maintenance): int
    {
        $section = $this->sectionKey();
        $label = $maintenance->label($section);

        if ($this->puttingDown()) {
            $message = $this->option('message');
            $maintenance->down($section, is_string($message) && $message !== '' ? $message : null);

            $this->components->warn("{$label} is now in maintenance mode.");
            $this->line('Visitors will see a maintenance page until you run the matching *-web-up command.');

            return self::SUCCESS;
        }

        $maintenance->up($section);

        $this->components->info("{$label} is live again.");

        return self::SUCCESS;
    }
}
