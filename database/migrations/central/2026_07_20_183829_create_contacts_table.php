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
        Schema::connection('central')->create('contacts', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();

            $table->enum('company_size', [
                '1-10',
                '11-50',
                '51-200',
                '200+'
            ])->nullable();

            $table->enum('inquiry_type', [
                'demo',
                'pricing',
                'sales',
                'partnership',
                'support',
                'general'
            ])->default('general');

            $table->text('message');

            $table->enum('status', [
                'new',
                'contacted',
                'in_progress',
                'replied',
                'closed'
            ])->default('new');

            $table->text('admin_note')->nullable();

            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('replied_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('central')->dropIfExists('contacts');
    }
};
