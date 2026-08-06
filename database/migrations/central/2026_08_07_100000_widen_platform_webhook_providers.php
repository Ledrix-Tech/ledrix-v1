<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('platform_webhook_events')) {
            return;
        }

        $driver = Schema::connection('central')->getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::connection('central')->statement(
                "ALTER TABLE platform_webhook_events MODIFY COLUMN provider ENUM('stripe','paypal','payfast','jazzcash','meezan','payoneer') NOT NULL DEFAULT 'stripe'"
            );
        }
        // SQLite / others: original create used string-like enum; tests recreate schema as needed.
    }

    public function down(): void
    {
        if (! Schema::connection('central')->hasTable('platform_webhook_events')) {
            return;
        }

        $driver = Schema::connection('central')->getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::connection('central')->statement(
                "ALTER TABLE platform_webhook_events MODIFY COLUMN provider ENUM('stripe','paypal') NOT NULL DEFAULT 'stripe'"
            );
        }
    }
};
