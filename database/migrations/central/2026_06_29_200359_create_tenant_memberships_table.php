<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('tenant_memberships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('plan_id')
                ->constrained('package_pricings')
                ->cascadeOnDelete();

            // billing_cycle was completely missing in old version
            $table->enum('billing_cycle', ['monthly', 'yearly'])
                ->default('monthly');

            // Stripe Cashier subscription ID
            $table->string('stripe_subscription_id')
                ->nullable()
                ->unique();

            // amount decimal — not varchar
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->string('currency', 3)->default('USD');

            // api_key moved to membership level
            // so each renewal gets a fresh key
            // tenant can use this to connect external tools
            $table->string('api_key', 64)->unique();

            // Dates
            $table->date('start_date');
            $table->date('end_date')->nullable()->index();

            // Trial tracking
            $table->date('trial_start')->nullable();
            $table->date('trial_end')->nullable();
            $table->timestamp('trial_reminder_sent_at')->nullable();
            // Cancellation
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            // Who renewed
            $table->enum('renewed_by', [
                'stripe',
                'super_admin',
                'tenant'
            ])->default('stripe');

            $table->string('conversion_source')->nullable();
            // Status
            // Should be:
            $table->enum('status', [
                'trialing',              // Trial active — full access
                'trialing_restricted',   // Trial ended — soft wall
                'active',                // Paying — full access
                'past_due',              // Payment failed — grace period
                'suspended',             // Hard suspended — no access
                'cancelled',             // Cancelled — access till period end
                'expired',               // Period ended — data preserved
            ])->default('trialing')->index();

            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_memberships');
    }
};
