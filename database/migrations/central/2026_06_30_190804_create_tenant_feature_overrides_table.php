<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        // FEATURE OVERRIDES
        // null column = use plan default
        // true  = force ON regardless of plan
        // false = force OFF regardless of plan
        Schema::connection('central')->create('tenant_feature_overrides', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                  ->unique()
                  ->constrained('tenants')
                  ->cascadeOnDelete();

            // null = plan default   true = on   false = off
            $table->boolean('feature_ppc_module')->nullable();
            $table->boolean('feature_upwork_module')->nullable();
            $table->boolean('feature_milestone_payments')->nullable();
            $table->boolean('feature_stripe')->nullable();
            $table->boolean('feature_paypal')->nullable();
            $table->boolean('feature_webhooks')->nullable();
            $table->boolean('feature_chargeback_tracking')->nullable();
            $table->boolean('feature_dual_invoicing')->nullable();
            $table->boolean('feature_client_portal')->nullable();
            $table->boolean('feature_lead_prediction')->nullable();
            $table->boolean('feature_seller_leaderboard')->nullable();
            $table->boolean('feature_performance_bonus')->nullable();
            $table->boolean('feature_projects')->nullable();
            $table->boolean('feature_support_tickets')->nullable();
            $table->boolean('feature_api_access')->nullable();
            $table->boolean('feature_custom_domain')->nullable();
            $table->boolean('feature_white_label')->nullable();

            $table->text('override_reason')->nullable();

            $table->foreignId('overridden_by')
                  ->nullable()
                  ->constrained('super_admins')
                  ->nullOnDelete();

            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_feature_overrides');
    }
};