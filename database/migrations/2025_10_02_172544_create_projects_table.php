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
        Schema::create('projects', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $t->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $t->foreignId('front_seller_id')->constrained('sellers');
            $t->foreignId('owner_seller_id')->constrained('sellers');
            $t->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $t->date('start_date')->nullable();
            $t->date('due_date')->nullable();
            $t->text('description')->nullable();
            $t->json('meta')->nullable();
            $t->timestamp('pm_assigned_at')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
