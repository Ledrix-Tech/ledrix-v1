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
        Schema::create('performance_bonuses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('seller_id')->constrained('sellers')->cascadeOnDelete();
            $t->foreignId('brand_id')->nullable()->constrained('brands')->cascadeOnDelete();
            $t->decimal('target_revenue', 12, 2);
            $t->decimal('bonus_amount', 12, 2);
            $t->date('period_start')->nullable();
            $t->date('period_end')->nullable();
            $t->string('currency', 10)->default('USD');
            $t->string('status')->default('pending');
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('performance_bonuses');
    }
};
