<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::connection('central')->hasTable('super_admin_password_resets')) {
            return;
        }

        // email length 191 keeps utf8mb4 unique/primary under MySQL's 1000-byte index limit
        Schema::connection('central')->create('super_admin_password_resets', function (Blueprint $table) {
            $table->string('email', 191)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('super_admin_password_resets');
    }
};
