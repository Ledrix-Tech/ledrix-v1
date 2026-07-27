<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('tenant_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                  ->constrained('tenants')
                  ->cascadeOnDelete();

            $table->foreignId('membership_id')
                  ->nullable()
                  ->constrained('tenant_memberships')
                  ->nullOnDelete();

            $table->foreignId('plan_id')
                  ->nullable()
                  ->constrained('package_pricings')
                  ->nullOnDelete();

            // Unique transaction IDs — idempotency
            $table->string('transaction_id')->unique();
            $table->string('payment_intent_id')->nullable()->index();

            // Payment context
            $table->enum('gateway', ['stripe', 'manual'])
                  ->default('stripe');

            $table->enum('order_type', [
                'new', 'renewal', 'upgrade', 'downgrade'
            ])->default('new');

            $table->enum('renewed_by', [
                'stripe', 'super_admin', 'tenant'
            ])->default('stripe');

            $table->enum('billing_cycle', ['monthly', 'yearly'])
                  ->default('monthly');

            // Amount — decimal not varchar
            $table->decimal('amount', 10, 2);
            $table->decimal('refunded_amount', 10, 2)->default(0.00);
            $table->string('currency', 5)->default('USD');

            // Refund tracking
            $table->enum('refund_status', [
                'none', 'partial', 'full'
            ])->default('none');

            // Status
            $table->enum('status', [
                'pending', 'paid', 'failed', 'refunded'
            ])->default('pending')->index();

            // Exact payment timestamp
            $table->timestamp('paid_at')->nullable();

            // Full Stripe/PayPal payload for debugging
            $table->json('payload')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_payments');
    }
};
