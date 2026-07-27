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
        Schema::create('payment_links', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $t->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $t->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $t->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $t->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $t->unsignedBigInteger('credit_to_seller_id')->nullable();
            $t->unsignedBigInteger('owner_seller_id')->nullable();
            $t->unsignedBigInteger('generated_by_id')->nullable();
            $t->string('generated_by_type', 30)->nullable();
            $t->string('service_name');
            $t->string('currency', 3)->default('USD');
            $t->enum('provider', ['stripe', 'paypal'])->default('stripe');
            $t->unsignedInteger('unit_amount');
            $t->unsignedInteger('order_total_snapshot')->nullable();
            $t->string('provider_session_id')->nullable();
            $t->string('provider_payment_intent_id')->nullable();
            $t->string('token')->unique();
            $t->enum('status', ['draft', 'active', 'paid', 'completed', 'canceled', 'expired'])->default('active');
            $t->string('expires_at')->nullable();
            $t->boolean('is_active_link')->default(true);
            $t->text('last_issued_url')->nullable();
            $t->timestamp('last_issued_at')->nullable();
            $t->timestamp('last_issued_expires_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_links');
    }
};
