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
        Schema::create('account_keys', function (Blueprint $t) {
            $t->id();
            $t->enum('module', ['upwork', 'ppc'])->default('ppc');
            $t->foreignId('brand_id')->nullable()->constrained('brands')->cascadeOnDelete();
            $t->string('brand_url')->nullable();
            $t->text('stripe_publishable_key')->nullable();
            $t->text('stripe_secret_key')->nullable();
            $t->string('stripe_webhook_secret')->nullable();
            $t->text('paypal_client_id')->nullable();
            $t->text('paypal_secret')->nullable();
            $t->string('paypal_webhook_id')->nullable();
            $t->string('paypal_base_url')->nullable();
            $t->enum('status', ['active', 'inactive'])->default('active');
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_keys');
    }
};
