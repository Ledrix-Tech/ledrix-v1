<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('tenant_invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                  ->constrained('tenants')
                  ->cascadeOnDelete();

            $table->foreignId('membership_id')
                  ->nullable()
                  ->constrained('tenant_memberships')
                  ->nullOnDelete();

            $table->foreignId('payment_id')
                  ->nullable()
                  ->constrained('tenant_payments')
                  ->nullOnDelete();

            // Invoice number — LDX-2026-0001
            $table->string('invoice_number')->unique();

            // Snapshot of plan name at time of invoice
            // Plan could change later, keep record
            $table->string('plan_name')->nullable();
            $table->enum('billing_cycle', ['monthly', 'yearly'])
                  ->nullable();

            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('tax_amount', 10, 2)->default(0.00);
            $table->decimal('total_amount', 10, 2);

            $table->enum('status', [
                'draft', 'issued', 'paid', 'void'
            ])->default('draft');

            // PDF stored in storage
            $table->string('pdf_path')->nullable();

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_invoices');
    }
};
