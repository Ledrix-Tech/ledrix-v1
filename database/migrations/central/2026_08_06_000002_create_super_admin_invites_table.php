<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('central')->hasTable('super_admin_invites')) {
            return;
        }

        Schema::connection('central')->create('super_admin_invites', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('name');
            $table->string('email');
            $table->enum('role', ['admin', 'support'])->default('support');
            $table->foreignId('invited_by')
                ->nullable()
                ->constrained('super_admins')
                ->nullOnDelete();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['email', 'accepted_at']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('super_admin_invites');
    }
};
