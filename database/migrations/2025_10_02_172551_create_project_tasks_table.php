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
        Schema::create('project_tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $t->string('title');
            $t->longText('description')->nullable();
            $t->foreignId('assigned_to')->nullable()->constrained('sellers')->nullOnDelete();
            $t->enum('status', ['pending', 'in_progress', 'completed', 'blocked'])->default('pending');
            $t->date('due_date')->nullable();
            $t->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('low');
            $t->json('meta')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
