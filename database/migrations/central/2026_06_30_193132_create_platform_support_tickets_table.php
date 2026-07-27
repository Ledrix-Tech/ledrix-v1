<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('platform_support_tickets', function (Blueprint $table) {
            $table->id();

            // Tenant who opened the ticket
            $table->foreignId('tenant_id')
                  ->constrained('tenants')
                  ->cascadeOnDelete();

            // Which super admin is handling it
            $table->foreignId('assigned_to')
                  ->nullable()
                  ->constrained('super_admins')
                  ->nullOnDelete();

            $table->string('subject');
            $table->longText('description');

            $table->enum('category', [
                'billing',
                'technical',
                'feature_request',
                'account',
                'other',
            ])->default('other');

            $table->enum('priority', [
                'low', 'medium', 'high', 'urgent'
            ])->default('medium');

            $table->enum('status', [
                'open',
                'in_progress',
                'on_hold',
                'resolved',
                'closed',
            ])->default('open')->index();

            $table->timestamp('first_replied_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['assigned_to', 'status']);
        });

        
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('platform_support_tickets');
    }
};