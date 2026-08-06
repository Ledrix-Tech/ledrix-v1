<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('platform_billing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40)->unique(); // stripe | payfast | meezan
            $table->boolean('enabled')->default(false);
            $table->text('credentials')->nullable(); // encrypted JSON
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('platform_billing_settings');
    }
};
