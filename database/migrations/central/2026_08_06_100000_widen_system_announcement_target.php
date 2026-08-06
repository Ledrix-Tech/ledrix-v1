<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('system_announcements')) {
            return;
        }

        // Allow current plan slugs (crm-basic, etc.) instead of legacy enum values.
        DB::connection('central')->statement(
            'ALTER TABLE system_announcements MODIFY COLUMN target VARCHAR(80) NOT NULL DEFAULT \'all\''
        );
    }

    public function down(): void
    {
        // Keep as VARCHAR — shrinking back to legacy ENUM would break new targets.
    }
};
