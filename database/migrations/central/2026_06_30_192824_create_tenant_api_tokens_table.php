<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('tenant_api_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                  ->constrained('tenants')
                  ->cascadeOnDelete();

            // Friendly name tenant gives the token
            $table->string('name');

            // Hashed token stored in DB
            // Plain token returned only once on creation
            $table->string('token', 64)->unique();

            // Scoped abilities — what this token can do
            // Example: ['leads:read', 'payments:write', '*']
            $table->json('abilities')->nullable();

            // Usage tracking
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();

            // Optional expiry — null = never expires
            $table->timestamp('expires_at')->nullable();

            $table->enum('status', ['active', 'revoked'])
                  ->default('active')->index();

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_api_tokens');
    }
};