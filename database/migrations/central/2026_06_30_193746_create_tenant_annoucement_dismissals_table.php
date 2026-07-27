<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        // Tracks which tenants have dismissed which announcements
        Schema::connection('central')->create('tenant_announcement_dismissals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                  ->constrained('tenants')
                  ->cascadeOnDelete();

            $table->foreignId('announcement_id')
                  ->constrained('system_announcements')
                  ->cascadeOnDelete();

            $table->timestamp('dismissed_at')->useCurrent();

            // One dismissal per tenant per announcement
            $table->unique(['tenant_id', 'announcement_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_announcement_dismissals');
    }
};