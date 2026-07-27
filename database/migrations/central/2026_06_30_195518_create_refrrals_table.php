<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('referrals', function (Blueprint $table) {
            $table->id();

            // Tenant who shared the referral code
            $table->foreignId('referrer_tenant_id')
                  ->constrained('tenants')
                  ->cascadeOnDelete();

            // Tenant who signed up using the code
            // null until someone uses the code
            $table->foreignId('referred_tenant_id')
                  ->nullable()
                  ->constrained('tenants')
                  ->nullOnDelete();

            // Unique referral code e.g. NOVA2026
            $table->string('referral_code', 20)->unique();

            // Reward configuration
            $table->enum('reward_type', [
                'credit',    // Account credit
                'discount',  // % off next invoice
                'cash',      // Actual payout
            ])->default('credit');

            $table->decimal('reward_amount', 10, 2)->default(0.00);
            $table->string('currency', 3)->default('USD');

            $table->enum('status', [
                'pending',    // Code exists, not used yet
                'converted',  // Someone signed up
                'rewarded',   // Referrer got their reward
                'expired',    // Code expired unused
            ])->default('pending');

            $table->timestamp('converted_at')->nullable();
            $table->timestamp('rewarded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['referrer_tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('referrals');
    }
};