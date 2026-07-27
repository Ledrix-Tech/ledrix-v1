<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {

        // Password reset tokens
        Schema::connection('central')->create('tenant_password_resets', function (Blueprint $table) {
            $table->id();

            $table->string('email')->index();

            // Hashed token stored in DB
            // Plain token sent to email
            $table->string('token');

            $table->timestamp('expires_at');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_password_resets');
    }
};