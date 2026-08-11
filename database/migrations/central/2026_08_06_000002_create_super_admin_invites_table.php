<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Previous failed run can leave a table without the composite index.
        Schema::connection('central')->dropIfExists('super_admin_invites');

        Schema::connection('central')->create('super_admin_invites', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('name');
            // 191 keeps utf8mb4 indexes under MySQL's 1000-byte limit with accepted_at
            $table->string('email', 191);
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
