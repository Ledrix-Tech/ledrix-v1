<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->table('tenant_memberships', function (Blueprint $table) {
            $table->timestamp('renewal_reminder_7d_sent_at')->nullable()->after('trial_reminder_sent_at');
            $table->timestamp('renewal_reminder_1d_sent_at')->nullable()->after('renewal_reminder_7d_sent_at');
            $table->timestamp('renewal_expired_notice_sent_at')->nullable()->after('renewal_reminder_1d_sent_at');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('tenant_memberships', function (Blueprint $table) {
            $table->dropColumn([
                'renewal_reminder_7d_sent_at',
                'renewal_reminder_1d_sent_at',
                'renewal_expired_notice_sent_at',
            ]);
        });
    }
};
