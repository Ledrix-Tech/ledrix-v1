<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->create('tenants', function (Blueprint $table) {
            $table->id();

            // Current active plan — FK to plans
            // Removed 'package' varchar — was wrong type
            $table->foreignId('plan_id')
                ->nullable()
                ->constrained('package_pricings')
                ->nullOnDelete();

            // Identity
            $table->string('name');

            // slug = subdomain → nova.ledrix.app
            $table->string('slug', 100)->unique();

            $table->string('email')->unique();
            $table->string('password');
            $table->string('phone', 50)->nullable();
            $table->string('country', 5)->nullable();
            $table->text('address')->nullable();
            $table->string('website')->nullable();
            $table->string('logo')->nullable();
            $table->string('timezone')->default('UTC');

            // Billing details
            $table->string('billing_name')->nullable();
            $table->string('billing_email')->nullable();
            $table->string('billing_phone', 50)->nullable();
            $table->text('billing_address')->nullable();

            // Stripe          
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('stripe_payment_method_id')->nullable();
            $table->string('stripe_setup_intent_id')->nullable();

            // Custom domain — Enterprise plan only
            // Agency points crm.theiragency.com → their slug
            $table->string('custom_domain')->nullable()->unique();
            $table->boolean('custom_domain_verified')->default(false);

            // Trial — 14 day free trial on registration
            $table->timestamp('trial_ends_at')->nullable();
            $table->boolean('trial_used')->default(false);
            $table->timestamp('card_collected_at')->nullable();
            // Status — 4 distinct states
            // inactive   = just registered, not verified/paid yet
            // active     = paying or on trial
            // suspended  = admin suspended (non-payment etc)
            // cancelled  = tenant cancelled themselves
            $table->enum('status', [
                'active',
                'inactive',
                'suspended',
                'cancelled'
            ])->default('inactive')->index();

            $table->longText('suspended_reason')->nullable();
            $table->timestamp('suspended_at')->nullable();

            // Extra meta for future flexibility
            $table->json('meta')->nullable();

            // Security and tracking
            $table->string('registered_ip', 45)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();

            $table->rememberToken();
            $table->timestamps();

            // Soft delete — never hard delete a tenant
            // Keep their record for billing history
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenants');
    }
};
