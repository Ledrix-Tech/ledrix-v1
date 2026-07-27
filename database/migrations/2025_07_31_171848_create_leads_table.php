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
        // leads migration — FIXED
        Schema::create('leads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('seller_id')->constrained()->cascadeOnDelete();
            $t->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $t->foreignId('client_id')->nullable()->constrained()->nullOnDelete();

            $t->string('name');
            $t->string('email');
            $t->string('phone')->nullable();
            $t->string('service')->nullable();
            $t->longText('message')->nullable();
            $t->enum('status', [
                'new',
                'contacted',
                'qualified',
                'proposal_sent',
                'first_paid',
                'in_progress',
                'completed',
                'renewal_due',
                'on_hold',
                'disqualified',
                'cancelled',
            ])->default('new');

            $t->boolean('auto_replied')->default(false);
            $t->timestamp('converted_at')->nullable();
            $t->string('domain_url')->nullable();
            $t->json('prediction')->nullable();
            $t->json('meta')->nullable();
            $t->boolean('is_finish')->default(false);

            $t->timestamps();
            $t->softDeletes();
            $t->index(['brand_id', 'seller_id', 'status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
