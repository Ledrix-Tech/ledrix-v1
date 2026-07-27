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
        Schema::create('questionnairs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')
                ->constrained('clients')
                ->onDelete('cascade');

            $table->foreignId('order_id')
                ->constrained('orders')
                ->onDelete('cascade');

            $table->string('service_name')->nullable();

            // Store form data as JSON
            $table->json('meta')->nullable();

            // REMOVE ->after() because this is a CREATE TABLE
            $table->char('brief_token', 36)->nullable();
            $table->timestamp('brief_token_expires_at')->nullable();

            // Track the brief status
            $table->enum('status', ['pending', 'progress', 'completed'])->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questionnairs', function (Blueprint $table) {
            $table->dropColumn(['brief_token', 'brief_token_expires_at']);
        });
    }
};
