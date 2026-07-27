<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $t->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $t->foreignId('seller_id')->constrained()->cascadeOnDelete();       // FS
            $t->foreignId('client_id')->constrained()->cascadeOnDelete();

            $t->unsignedBigInteger('parent_order_id')->nullable();
            $t->enum('order_type', ['original', 'renewal'])->default('original');

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

            $t->unsignedBigInteger('front_seller_id')->nullable();
            $t->unsignedBigInteger('owner_seller_id')->nullable(); // PM or FS
            $t->unsignedBigInteger('opened_by_seller_id')->nullable();

            $t->unsignedInteger('front_credits_used')->default(0);
            $t->unsignedBigInteger('front_credited_cents')->default(0);

            $t->timestamp('first_paid_at')->nullable();
            $t->timestamp('paid_at')->nullable();

            $t->unsignedInteger('refunded_amount')->default(0);
            $t->enum('refund_status', ['none', 'partial', 'full', 'chargeback'])->default('none');

            $t->string('provider_session_id')->nullable();
            $t->string('provider_payment_intent_id')->nullable();
            $t->string('buyer_name')->nullable();
            $t->string('buyer_email')->nullable();
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
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('orders');
        Schema::enableForeignKeyConstraints();
    }
};
