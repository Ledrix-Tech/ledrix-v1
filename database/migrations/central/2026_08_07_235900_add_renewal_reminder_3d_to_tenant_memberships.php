<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        if (! Schema::connection('central')->hasColumn('tenant_memberships', 'renewal_reminder_3d_sent_at')) {
            Schema::connection('central')->table('tenant_memberships', function (Blueprint $table) {
                $table->timestamp('renewal_reminder_3d_sent_at')->nullable()->after('renewal_reminder_7d_sent_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('central')->hasColumn('tenant_memberships', 'renewal_reminder_3d_sent_at')) {
            Schema::connection('central')->table('tenant_memberships', function (Blueprint $table) {
                $table->dropColumn('renewal_reminder_3d_sent_at');
            });
        }
    }
};
