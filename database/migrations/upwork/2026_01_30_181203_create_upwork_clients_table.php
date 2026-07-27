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
        Schema::create('upwork_clients', function (Blueprint $t) {
            $t->id();
            $t->foreignId('brand_id')->nullable()
                ->constrained('brands')
                ->nullOnDelete();

            $t->string('name');
            $t->string('email')->unique();
            $t->string('password')->nullable();
            $t->string('phone')->nullable();
            $t->json('meta')->nullable();
            $t->enum('status', ['Active', 'Inactive'])->default('Active');
            $t->timestamps();
            $t->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upwork_clients');
    }
};
