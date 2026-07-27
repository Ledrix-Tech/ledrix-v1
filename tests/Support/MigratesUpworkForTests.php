<?php

namespace Tests\Support;

trait MigratesUpworkForTests
{
    protected function migrateUpworkTables(): void
    {
        $this->artisan('migrate', [
            '--path' => 'database/migrations/upwork',
            '--realpath' => false,
        ]);
    }
}
