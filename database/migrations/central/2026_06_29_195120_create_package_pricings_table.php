<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('package_pricings', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('name');
            $table->string('slug')->unique();   // 'seed','growth','agency','enterprise'
            $table->text('description')->nullable();

            // Stripe price IDs for Laravel Cashier
            $table->string('stripe_monthly_price_id')->nullable();
            $table->string('stripe_yearly_price_id')->nullable();

            // Pricing — decimal not varchar
            $table->decimal('monthly_price', 10, 2)->default(0.00);
            $table->decimal('yearly_price', 10, 2)->default(0.00);
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('trial_days')->default(14);

            // ── Resource Limits ────────────────────────────
            // -1 = unlimited for any column
            // Stored as signed integer so -1 is valid
            $table->integer('max_brands')->default(1);
            $table->integer('max_sellers')->default(2);
            $table->integer('max_admins')->default(1);
            $table->integer('max_clients')->default(50);
            $table->integer('max_leads_per_month')->default(50);
            $table->integer('max_orders')->default(50);
            $table->integer('max_payment_links')->default(50);
            $table->integer('max_account_keys')->default(1);
            $table->integer('max_projects')->default(0);
            $table->integer('max_storage_mb')->default(512);

            // ── Feature Flags ──────────────────────────────
            // Individual boolean columns — NOT JSON
            // Enables: if ($plan->feature_webhooks) directly
            // Enables: $tenant->can('webhooks') without parsing
            $table->boolean('feature_ppc_module')->default(true);
            $table->boolean('feature_upwork_module')->default(false);
            $table->boolean('feature_milestone_payments')->default(false);
            $table->boolean('feature_stripe')->default(true);
            $table->boolean('feature_paypal')->default(false);
            $table->boolean('feature_webhooks')->default(false);
            $table->boolean('feature_chargeback_tracking')->default(false);
            $table->boolean('feature_dual_invoicing')->default(false);
            $table->boolean('feature_client_portal')->default(false);
            $table->boolean('feature_lead_prediction')->default(false);
            $table->boolean('feature_seller_leaderboard')->default(false);
            $table->boolean('feature_performance_bonus')->default(false);
            $table->boolean('feature_projects')->default(false);
            $table->boolean('feature_support_tickets')->default(false);
            $table->boolean('feature_api_access')->default(false);
            $table->boolean('feature_custom_domain')->default(false);
            $table->boolean('feature_white_label')->default(false);

            // ── Display ────────────────────────────────────
            $table->boolean('is_popular')->default(false);
            $table->boolean('is_public')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('badge_text')->nullable();

            // Keep HTML for display only — not for logic
            $table->longText('features_html')->nullable();

            $table->enum('status', ['active', 'inactive'])
                  ->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('package_pricings');
    }
};