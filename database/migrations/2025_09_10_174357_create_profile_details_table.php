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
        Schema::create('profile_details', function (Blueprint $t) {
            $t->id();
            $t->morphs('user');
            $t->string('profile')->nullable();
            $t->string('name')->nullable();
            $t->string('email')->unique();
            $t->string('alternate_email')->nullable();
            $t->string('phone')->nullable();
            $t->longText('address')->nullable();
            $t->string('status')->default('active');
            $t->timestamps();
            $t->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_details');
    }
};
