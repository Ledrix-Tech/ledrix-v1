<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        if (! Schema::connection('central')->hasColumn('demo_requests', 'tenant_id')) {
            Schema::connection('central')->table('demo_requests', function (Blueprint $table) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tenants')
                    ->nullOnDelete();
            });
        }

        if (! Schema::connection('central')->hasColumn('demo_requests', 'demo_expires_at')) {
            Schema::connection('central')->table('demo_requests', function (Blueprint $table) {
                $table->timestamp('demo_expires_at')->nullable()->after('demo_sent_at');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->table('demo_requests', function (Blueprint $table) {
            if (Schema::connection('central')->hasColumn('demo_requests', 'tenant_id')) {
                $table->dropConstrainedForeignId('tenant_id');
            }
            if (Schema::connection('central')->hasColumn('demo_requests', 'demo_expires_at')) {
                $table->dropColumn('demo_expires_at');
            }
        });
    }
};
