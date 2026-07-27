<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            UPDATE payments p
            INNER JOIN orders o ON o.id = p.order_id
            SET p.tenant_id = o.tenant_id
            WHERE p.tenant_id IS NULL
              AND o.tenant_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        // Non-reversible data backfill.
    }
};
