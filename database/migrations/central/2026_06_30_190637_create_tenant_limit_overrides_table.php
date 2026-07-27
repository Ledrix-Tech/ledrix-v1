<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        // LIMIT OVERRIDES
        // null column = use plan default
        // integer value = override for this tenant only
        // -1 = unlimited for this tenant regardless of plan
        Schema::connection('central')->create('tenant_limit_overrides', function (Blueprint $table) {
            $table->id();

            // One override row per tenant
            $table->foreignId('tenant_id')
                  ->unique()
                  ->constrained('tenants')
                  ->cascadeOnDelete();

            // All signed integers so -1 = unlimited is valid
            // null = fall back to plan default
            $table->integer('max_brands')->nullable();
            $table->integer('max_sellers')->nullable();
            $table->integer('max_admins')->nullable();
            $table->integer('max_clients')->nullable();
            $table->integer('max_leads_per_month')->nullable();
            $table->integer('max_orders')->nullable();
            $table->integer('max_payment_links')->nullable();
            $table->integer('max_account_keys')->nullable();
            $table->integer('max_projects')->nullable();
            $table->integer('max_storage_mb')->nullable();

            $table->text('override_reason')->nullable();

            $table->foreignId('overridden_by')
                  ->nullable()
                  ->constrained('super_admins')
                  ->nullOnDelete();

            // Override can expire automatically
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_limit_overrides');
    }
};