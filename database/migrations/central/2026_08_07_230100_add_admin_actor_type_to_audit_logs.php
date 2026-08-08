<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('audit_logs')) {
            return;
        }

        $driver = Schema::connection('central')->getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::connection('central')->statement(
                "ALTER TABLE audit_logs MODIFY actor_type ENUM(
                    'super_admin',
                    'tenant',
                    'admin',
                    'seller',
                    'system',
                    'api'
                ) NOT NULL DEFAULT 'system'"
            );
        }
        // sqlite / pgsql tests already use a free-form string column
    }

    public function down(): void
    {
        if (! Schema::connection('central')->hasTable('audit_logs')) {
            return;
        }

        $driver = Schema::connection('central')->getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::connection('central')->statement(
                "ALTER TABLE audit_logs MODIFY actor_type ENUM(
                    'super_admin',
                    'tenant',
                    'seller',
                    'system',
                    'api'
                ) NOT NULL DEFAULT 'system'"
            );
        }
    }
};
