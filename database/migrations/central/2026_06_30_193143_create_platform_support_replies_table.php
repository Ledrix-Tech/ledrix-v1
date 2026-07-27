<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('platform_support_replies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ticket_id')
                ->constrained('platform_support_tickets')
                ->cascadeOnDelete();

            // Who sent this reply
            $table->enum('sender_type', ['super_admin', 'tenant']);
            $table->unsignedBigInteger('sender_id');

            $table->longText('message');
            $table->string('attachment_path')->nullable();

            // Internal notes visible only to super admins
            $table->boolean('is_internal')->default(false);

            $table->timestamps();

            $table->index(['sender_type', 'sender_id']);
            $table->index(['ticket_id', 'is_internal']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('platform_support_replies');
    }
};
