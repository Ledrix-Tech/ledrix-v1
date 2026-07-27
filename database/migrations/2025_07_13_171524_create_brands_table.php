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
        Schema::create('brands', function (Blueprint $t) {
            $t->id();
            $t->enum('module', ['upwork', 'ppc'])->default('ppc');
            $t->string('brand_name');
            $t->string('brand_url');
            $t->string('brand_host')->nullable()->index();
            $t->json('allowed_origins')->nullable();
            $t->string('public_form_token')->nullable();
            $t->string('webhook_secret')->nullable();
            $t->boolean('require_hmac')->default(false)->index();
            $t->longText('lead_script')->nullable();
            $t->json('field_mapping')->nullable(); // site form field → CRM field mapping
            $t->enum('status', ['Pending', 'Active'])->default('Active');
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
