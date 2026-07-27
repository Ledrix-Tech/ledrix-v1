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
        Schema::create('lead_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('assigned_to');
            $t->string('assigned_role');
            $t->unsignedBigInteger('assigned_by');
            $t->timestamp('assigned_at')->useCurrent();
            $t->enum('status', [
                'pending',
                'assigned',
                'in_progress',
                'on_hold',
                'completed',
                'refund_requested',
                'chargeback',
                'rejected_by_client',
                'cancelled'
            ])->default('pending');
            $t->timestamps();
            $t->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_assignments');
    }
};
