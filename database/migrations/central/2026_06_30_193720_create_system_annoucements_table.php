<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('system_announcements', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->longText('message');

            // Visual type for alert styling
            $table->enum('type', [
                'info', 'warning', 'success', 'danger'
            ])->default('info');

            // Target specific plan or all tenants
            $table->enum('target', [
                'all',
                'plan_seed',
                'plan_growth',
                'plan_agency',
                'plan_enterprise',
            ])->default('all');

            // Can tenant dismiss this banner
            $table->boolean('is_dismissible')->default(true);

            // Show within this window only
            $table->timestamp('show_from')->nullable();
            $table->timestamp('show_until')->nullable();

            $table->enum('status', ['active', 'inactive'])
                  ->default('active');

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('super_admins')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('system_announcements');
    }
};