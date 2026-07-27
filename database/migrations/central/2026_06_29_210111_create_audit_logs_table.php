<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Tenant context — null means platform-level action
            $table->foreignId('tenant_id')
                  ->nullable()
                  ->constrained('tenants')
                  ->nullOnDelete();

            // Who performed the action
            // actor_type covers all possible actors
            $table->enum('actor_type', [
                'super_admin',  // Ledrix team member
                'tenant',       // Tenant owner/admin
                'seller',       // Seller inside tenant app
                'system',       // Automated scheduled job
                'api',          // API token request
            ])->default('system');

            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();

            // What happened
            // Examples: 'plan.changed' 'tenant.suspended'
            //           'membership.created' 'feature.override'
            $table->string('action', 100)->index();

            // What was affected
            $table->string('subject_type', 100)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->text('description')->nullable();

            // Before and after state
            $table->json('before')->nullable();
            $table->json('after')->nullable();

            // Security context
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // No updated_at — audit logs are immutable
            $table->timestamp('created_at')
                  ->useCurrent()
                  ->index();

            // Composite indexes for fast queries
            $table->index(['actor_type', 'actor_id']);
            $table->index(['tenant_id', 'action']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('audit_logs');
    }
};