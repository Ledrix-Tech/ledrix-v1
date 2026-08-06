<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        // Idempotent: keep full ENUM so existing bank_transfer / jazzcash rows are safe.
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
        // Do not shrink ENUM — would truncate live payment rows.
    }
};
