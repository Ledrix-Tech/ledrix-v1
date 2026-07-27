<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'central';

    /**
     * Package plans use -1 for unlimited; tenant_limits must store the same values.
     */
    public function up(): void
    {
        $columns = [
            'max_admins',
            'max_users',
            'max_clients',
            'max_brands',
            'max_sellers',
            'max_leads',
            'max_orders',
            'max_payment_links',
            'max_payments',
            'max_projects',
            'max_storage_mb',
        ];

        foreach ($columns as $column) {
            DB::connection('central')->statement(
                "ALTER TABLE tenant_limits MODIFY {$column} INT NOT NULL"
            );
        }
    }

    public function down(): void
    {
        $defaults = [
            'max_admins'        => 2,
            'max_users'         => 10,
            'max_clients'       => 100,
            'max_brands'        => 2,
            'max_sellers'       => 5,
            'max_leads'         => 500,
            'max_orders'        => 500,
            'max_payment_links' => 200,
            'max_payments'      => 200,
            'max_projects'      => 100,
            'max_storage_mb'    => 512,
        ];

        foreach ($defaults as $column => $default) {
            DB::connection('central')->statement(
                "UPDATE tenant_limits SET {$column} = 0 WHERE {$column} < 0"
            );

            DB::connection('central')->statement(
                "ALTER TABLE tenant_limits MODIFY {$column} INT UNSIGNED NOT NULL DEFAULT {$default}"
            );
        }
    }
};
