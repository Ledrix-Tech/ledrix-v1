<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        if (! Schema::connection('central')->hasColumn('tenant_memberships', 'renewal_reminder_7d_sent_at')) {
            Schema::connection('central')->table('tenant_memberships', function (Blueprint $table) {
                $table->timestamp('renewal_reminder_7d_sent_at')->nullable()->after('trial_reminder_sent_at');
            });
        }

        if (! Schema::connection('central')->hasColumn('tenant_memberships', 'renewal_reminder_1d_sent_at')) {
            Schema::connection('central')->table('tenant_memberships', function (Blueprint $table) {
                $table->timestamp('renewal_reminder_1d_sent_at')->nullable();
            });
        }

        if (! Schema::connection('central')->hasColumn('tenant_memberships', 'renewal_expired_notice_sent_at')) {
            Schema::connection('central')->table('tenant_memberships', function (Blueprint $table) {
                $table->timestamp('renewal_expired_notice_sent_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->table('tenant_memberships', function (Blueprint $table) {
            foreach ([
                'renewal_reminder_7d_sent_at',
                'renewal_reminder_1d_sent_at',
                'renewal_expired_notice_sent_at',
            ] as $col) {
                if (Schema::connection('central')->hasColumn('tenant_memberships', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
