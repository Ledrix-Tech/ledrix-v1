<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->string('preferred_billing_currency', 3)->default('PKR')->after('billing_address');
            $table->boolean('auto_renew')->default(false)->after('preferred_billing_currency');
            $table->text('jazzcash_payment_token')->nullable()->after('auto_renew');
            $table->timestamp('jazzcash_token_expires_at')->nullable()->after('jazzcash_payment_token');
        });

        Schema::connection('central')->table('package_pricings', function (Blueprint $table) {
            $table->decimal('monthly_price_pkr', 10, 2)->nullable()->after('yearly_price');
            $table->decimal('yearly_price_pkr', 10, 2)->nullable()->after('monthly_price_pkr');
        });

        DB::connection('central')->statement(
            "ALTER TABLE tenant_payments MODIFY COLUMN gateway ENUM('stripe', 'manual', 'payoneer', 'jazzcash', 'bank_transfer') NOT NULL DEFAULT 'stripe'"
        );

        DB::connection('central')->statement(
            "ALTER TABLE tenant_payments MODIFY COLUMN renewed_by ENUM('stripe', 'super_admin', 'tenant', 'payoneer', 'jazzcash') NOT NULL DEFAULT 'stripe'"
        );
    }

    public function down(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'preferred_billing_currency',
                'auto_renew',
                'jazzcash_payment_token',
                'jazzcash_token_expires_at',
            ]);
        });

        Schema::connection('central')->table('package_pricings', function (Blueprint $table) {
            $table->dropColumn(['monthly_price_pkr', 'yearly_price_pkr']);
        });

        DB::connection('central')->statement(
            "ALTER TABLE tenant_payments MODIFY COLUMN gateway ENUM('stripe', 'manual', 'payoneer') NOT NULL DEFAULT 'stripe'"
        );

        DB::connection('central')->statement(
            "ALTER TABLE tenant_payments MODIFY COLUMN renewed_by ENUM('stripe', 'super_admin', 'tenant', 'payoneer') NOT NULL DEFAULT 'stripe'"
        );
    }
};
