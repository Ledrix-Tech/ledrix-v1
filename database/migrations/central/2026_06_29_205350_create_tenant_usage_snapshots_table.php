<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('tenant_usage_snapshots', function (Blueprint $table) {
            $table->id();

            // One snapshot row per tenant — updated in real time
            $table->foreignId('tenant_id')
                  ->unique()
                  ->constrained('tenants')
                  ->cascadeOnDelete();

            // Current usage counters
            // Updated every time a resource is created/deleted
            $table->unsignedInteger('total_brands')->default(0);
            $table->unsignedInteger('total_sellers')->default(0);
            $table->unsignedInteger('total_admins')->default(0);
            $table->unsignedInteger('total_clients')->default(0);
            $table->unsignedInteger('total_orders')->default(0);
            $table->unsignedInteger('total_payment_links')->default(0);
            $table->unsignedInteger('total_account_keys')->default(0);
            $table->unsignedInteger('total_projects')->default(0);

            // Monthly lead counter — resets each month
            $table->unsignedInteger('leads_this_month')->default(0);
            $table->timestamp('month_reset_at')->nullable();

            // Storage usage in MB
            $table->unsignedInteger('storage_used_mb')->default(0);

            // When was this last synced from primary DB
            $table->timestamp('last_synced_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_usage_snapshots');
    }
};