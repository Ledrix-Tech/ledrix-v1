<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Primary CRM tables that belong to a tenant.
     */
    private array $tenantTables = [
        'brands',
        'admins',
        'sellers',
        'clients',
        'leads',
        'orders',
        'payments',
        'payment_links',
        'account_keys',
        'lead_assignments',
        'profile_details',
        'risky_clients',
        'projects',
        'project_tasks',
        'client_tickets',
        'performance_bonuses',
        'questionnairs',
        'upwork_clients',
        'upwork_orders',
        'upwork_payment_links',
        'upwork_payments',
    ];

    /**
     * Tables where email must be unique per tenant (not globally).
     */
    private array $tenantScopedEmailUniques = [
        'admins',
        'sellers',
        'clients',
        'profile_details',
        'upwork_clients',
    ];

    public function up(): void
    {
        foreach ($this->tenantTables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'tenant_id')) {
                    $blueprint->unsignedBigInteger('tenant_id')
                        ->nullable()
                        ->after('id')
                        ->index();
                }
            });
        }

        $this->swapEmailUniquesToTenantScoped();

        $defaultTenantId = DB::connection('central')
            ->table('tenants')
            ->orderBy('id')
            ->value('id');

        if ($defaultTenantId) {
            foreach ($this->tenantTables as $table) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                    continue;
                }

                DB::table($table)
                    ->whereNull('tenant_id')
                    ->update(['tenant_id' => $defaultTenantId]);
            }
        }
    }

    public function down(): void
    {
        $this->restoreGlobalEmailUniques();

        foreach (array_reverse($this->tenantTables) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropIndex(['tenant_id']);
                $blueprint->dropColumn('tenant_id');
            });
        }
    }

    private function swapEmailUniquesToTenantScoped(): void
    {
        foreach ($this->tenantScopedEmailUniques as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if ($this->hasIndex($table, "{$table}_email_unique")) {
                    $blueprint->dropUnique(['email']);
                }

                $blueprint->unique(['tenant_id', 'email'], "{$table}_tenant_email_unique");
            });
        }
    }

    private function restoreGlobalEmailUniques(): void
    {
        foreach ($this->tenantScopedEmailUniques as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if ($this->hasIndex($table, "{$table}_tenant_email_unique")) {
                    $blueprint->dropUnique(["{$table}_tenant_email_unique"]);
                }

                if (Schema::hasColumn($table, 'email') && ! $this->hasIndex($table, "{$table}_email_unique")) {
                    $blueprint->unique('email');
                }
            });
        }
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection()->getDriverName();

        if ($connection !== 'mysql') {
            return true;
        }

        $database = Schema::getConnection()->getDatabaseName();

        $result = DB::select(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return ($result[0]->aggregate ?? 0) > 0;
    }
};
