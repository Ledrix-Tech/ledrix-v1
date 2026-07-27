<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('tenant_renewal_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                  ->constrained('tenants')
                  ->cascadeOnDelete();

            $table->foreignId('plan_id')
                  ->constrained('package_pricings')
                  ->cascadeOnDelete();

            // Unique token sent via email link
            $table->string('token')->unique();

            // Who triggered this request
            $table->string('requested_by_email')->nullable();

            $table->enum('billing_cycle', ['monthly', 'yearly'])
                  ->default('monthly');

            // Pre-calculated amount for this renewal
            $table->decimal('amount', 10, 2)->nullable();

            // cancelled status was missing in your version
            $table->enum('status', [
                'pending', 'approved', 'expired', 'cancelled'
            ])->default('pending');

            $table->timestamp('expires_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_renewal_requests');
    }
};