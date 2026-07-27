<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        // Email verification tokens
        Schema::connection('central')->create('tenant_email_verifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                  ->constrained('tenants')
                  ->cascadeOnDelete();

            $table->string('email');

            // Plain token sent in email
            // Not hashed — safe for email link only
            $table->string('token')->unique();

            $table->timestamp('expires_at');

            // No updated_at needed
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id', 'token']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_email_verifications');
    }
};