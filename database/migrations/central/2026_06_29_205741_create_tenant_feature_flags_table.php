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
        Schema::connection('central')->create('tenant_feature_flags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('feature_key');       // e.g. lead_prediction, seller_leaderboard, ai_scoring
            $table->boolean('is_enabled')->default(false);
            $table->date('enabled_from')->nullable();
            $table->date('enabled_until')->nullable();
            $table->json('meta')->nullable();     // extra config per feature if needed
            $table->timestamps();

            $table->unique(['tenant_id', 'feature_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_feature_flags');
    }
};
