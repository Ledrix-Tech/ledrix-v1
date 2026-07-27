<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('platform_webhook_events', function (Blueprint $table) {
            $table->id();

            // Tenant context — nullable because some
            // Stripe events arrive before tenant is resolved
            $table->foreignId('tenant_id')
                  ->nullable()
                  ->constrained('tenants')
                  ->nullOnDelete();

            $table->enum('provider', ['stripe', 'paypal'])
                  ->default('stripe');

            // Stripe event ID — UNIQUE constraint
            // This is the idempotency key
            // Same event arriving twice = only processed once
            $table->string('event_id')->unique();
            $table->string('event_type', 100)->index();

            // Full raw payload stored for debugging
            $table->json('payload');

            $table->enum('status', [
                'pending', 'processed', 'failed', 'ignored'
            ])->default('pending')->index();

            // Processing tracking
            $table->timestamp('processed_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('error_message')->nullable();

            $table->timestamps();

            // Composite index for common queries
            $table->index(['tenant_id', 'event_type']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('platform_webhook_events');
    }
};