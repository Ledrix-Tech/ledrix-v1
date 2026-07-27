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
        Schema::connection('central')->create('tenant_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained('package_pricings')->nullOnDelete();
            $table->unsignedInteger('max_admins')->default(2);
            $table->unsignedInteger('max_users')->default(10);
            $table->unsignedInteger('max_clients')->default(100);
            $table->unsignedInteger('max_brands')->default(2);     // LLC / brands limit
            $table->unsignedInteger('max_sellers')->default(5);
            $table->unsignedInteger('max_leads')->default(500);
            $table->unsignedInteger('max_orders')->default(500);
            $table->unsignedInteger('max_payment_links')->default(200);
            $table->unsignedInteger('max_payments')->default(200);
            $table->unsignedInteger('max_projects')->default(100);
            $table->unsignedInteger('max_storage_mb')->default(512); // file upload quota
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_limits');
    }
};
