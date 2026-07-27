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
        Schema::create('client_tickets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $t->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $t->foreignId('seller_id')->nullable()->constrained('sellers')->nullOnDelete();
            $t->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $t->string('subject');
            $t->longText('description');
            $t->string('attachment')->nullable();
            $t->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $t->enum('status', ['open', 'in_progress', 'on_hold', 'resolved', 'closed', 'reopened'])->default('open');
            $t->string('source')->default('crm');
            $t->boolean('is_client_visible')->default(true);
            $t->boolean('is_internal')->default(false);
            $t->timestamp('closed_at')->nullable();
            $t->unsignedBigInteger('closed_by')->nullable();
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_tickets');
    }
};
