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
        Schema::create('upwork_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->nullable()->constrained('upwork_orders')->cascadeOnDelete();
            $t->foreignId('payment_link_id')->nullable()->constrained('upwork_payment_links')->nullOnDelete();
            $t->unsignedInteger('amount');
            $t->string('currency', 3)->default('USD');
            $t->enum('status', ['pending', 'succeeded', 'failed', 'refunded', 'partially_refunded'])->default('pending');
            $t->string('provider')->default('stripe');
            $t->string('provider_payment_intent_id')->index();
            $t->json('payload')->nullable();
            $t->unsignedInteger('refunded_amount')->default(0);
            $t->enum('refund_status', ['none', 'partial', 'full', 'chargeback'])
                ->default('none');
            $t->json('refund_payload')->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->unique(['provider', 'provider_payment_intent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upwork_payments');
    }
};
