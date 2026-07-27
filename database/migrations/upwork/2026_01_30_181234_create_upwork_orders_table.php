<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('upwork_orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('brand_id')->nullable()
                ->constrained('brands')
                ->nullOnDelete();

            $t->foreignId('client_id')->references('id')->on('upwork_clients')->cascadeOnDelete();

            $t->unsignedBigInteger('parent_order_id')->nullable();
            $t->enum('order_type', ['original', 'renewal'])->default('original');
            $t->enum('sell_type', ['front', 'upsell'])->default('upsell');

            $t->string('service_name')->nullable();
            $t->string('currency', 3)->default('USD');

            $t->unsignedInteger('unit_amount');
            $t->unsignedInteger('amount_paid')->default(0);
            $t->unsignedInteger('balance_due')->default(0);

            $t->enum('status', [
                'draft',          // created
                'pending',        // partially paid
                'paid',           // fully paid
                'in_progress',    // PM started work (manual)
                'revision',       // client change request
                'completed',      // final delivered
                'refunded',       // refund done
                'canceled',       // order canceled
            ])->default('draft');

            $t->timestamp('paid_at')->nullable();

            $t->unsignedInteger('refunded_amount')->default(0);
            $t->enum('refund_status', ['none', 'partial', 'full', 'chargeback'])->default('none');

            $t->string('provider_session_id')->nullable();
            $t->string('provider_payment_intent_id')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->foreign('parent_order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upwork_orders');
    }
};
