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
        Schema::create('sellers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $t->string('name');
            $t->string('sudo_name')->nullable();
            $t->enum('is_seller', ['project_manager', 'front_seller'])->index();
            $t->string('email')->unique();
            $t->string('password');
            $t->enum('status', ['Active', 'Inactive'])->default('Active');
            $t->timestamp('last_seen')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sellers');
    }
};
