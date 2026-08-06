<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'central';

    /**
     * Expand gateway / renewed_by ENUMs. Never shrink — local/prod DBs may
     * already contain bank_transfer, jazzcash, or payfast rows.
     */
    public function up(): void
    {
        DB::connection('central')->statement(
            "ALTER TABLE tenant_payments MODIFY COLUMN gateway ENUM(
                'stripe', 'manual', 'payoneer', 'jazzcash', 'bank_transfer', 'payfast'
            ) NOT NULL DEFAULT 'stripe'"
        );

        DB::connection('central')->statement(
            "ALTER TABLE tenant_payments MODIFY COLUMN renewed_by ENUM(
                'stripe', 'super_admin', 'tenant', 'payoneer', 'jazzcash', 'payfast'
            ) NOT NULL DEFAULT 'stripe'"
        );
    }

    public function down(): void
    {
        // Intentionally leave expanded ENUM values — shrinking would truncate live rows.
    }
};
