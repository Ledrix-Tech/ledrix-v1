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
        if (! Schema::connection('central')->hasColumn('tenants', 'preferred_billing_currency')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->string('preferred_billing_currency', 3)->default('PKR')->after('billing_address');
            });
        }

        if (! Schema::connection('central')->hasColumn('tenants', 'auto_renew')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->boolean('auto_renew')->default(false);
            });
        }

        if (! Schema::connection('central')->hasColumn('tenants', 'jazzcash_payment_token')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->text('jazzcash_payment_token')->nullable();
            });
        }

        if (! Schema::connection('central')->hasColumn('tenants', 'jazzcash_token_expires_at')) {
            Schema::connection('central')->table('tenants', function (Blueprint $table) {
                $table->timestamp('jazzcash_token_expires_at')->nullable();
            });
        }

        if (! Schema::connection('central')->hasColumn('package_pricings', 'monthly_price_pkr')) {
            Schema::connection('central')->table('package_pricings', function (Blueprint $table) {
                $table->decimal('monthly_price_pkr', 10, 2)->nullable()->after('yearly_price');
            });
        }

        if (! Schema::connection('central')->hasColumn('package_pricings', 'yearly_price_pkr')) {
            Schema::connection('central')->table('package_pricings', function (Blueprint $table) {
                $table->decimal('yearly_price_pkr', 10, 2)->nullable();
            });
        }

        DB::connection('central')->statement(
            "ALTER TABLE tenant_payments MODIFY COLUMN gateway ENUM(
                'stripe', 'manual', 'payoneer', 'jazzcash', 'bank_transfer', 'payfast'
            ) NOT NULL DEFAULT 'stripe'"
        );

        DB::connection('central')->statement(
            "ALTER TABLE tenant_payments MODIFY COLUMN renewed_by ENUM(
                'stripe', 'super_admin', 'tenant', 'payoneer', 'jazzcash', 'payfast'
            ) NOT NULL DEFAULT 'stripe'"
        );
    }

    public function down(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            foreach (['preferred_billing_currency', 'auto_renew', 'jazzcash_payment_token', 'jazzcash_token_expires_at'] as $col) {
                if (Schema::connection('central')->hasColumn('tenants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::connection('central')->table('package_pricings', function (Blueprint $table) {
            foreach (['monthly_price_pkr', 'yearly_price_pkr'] as $col) {
                if (Schema::connection('central')->hasColumn('package_pricings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
