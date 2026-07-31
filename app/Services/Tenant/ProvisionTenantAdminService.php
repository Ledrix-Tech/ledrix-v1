<?php

namespace App\Services\Tenant;

use App\Models\Admin;
use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use App\Models\Central\TenantUsageSnapshot;
use App\Services\Tenant\TenantLimitService;
use App\Services\Tenant\TenantUsageService;

class ProvisionTenantAdminService
{
    public function __construct(
        private TenantLimitService $limits,
    ) {}

    /**
     * Find or create the primary CRM admin account for this tenant.
     * Copies the bcrypt hash so credentials match the tenant portal login.
     */
    public function provision(Tenant $tenant): Admin
    {
        $exists = Admin::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $tenant->email)
            ->exists();

        if (! $exists) {
            $this->limits->assertCanCreateAdmin((int) $tenant->id);
        }

        $admin = Admin::withoutGlobalScopes()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email'     => $tenant->email,
            ],
            [
                'name'     => $tenant->name,
                'password' => $tenant->password,
                'role'     => 'admin',
            ]
        );

        $this->syncAdminUsageCount($tenant);

        app(TenantUsageService::class)->syncSnapshot((int) $tenant->id);

        AuditLog::record(
            action: 'tenant.admin_provisioned',
            tenantId: $tenant->id,
            actorType: 'tenant',
            actorId: $tenant->id,
            actorName: $tenant->name,
            context: [
                'subject_type' => 'admin',
                'subject_id'   => $admin->id,
                'description'  => 'CRM admin account provisioned for tenant workspace.',
            ]
        );

        return $admin;
    }

    public function canAccessCrm(Tenant $tenant): bool
    {
        return app(SubscriptionAccessService::class)->canUseCrm($tenant);
    }

    private function syncAdminUsageCount(Tenant $tenant): void
    {
        $count = Admin::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->count();

        TenantUsageSnapshot::query()
            ->where('tenant_id', $tenant->id)
            ->update(['total_admins' => $count, 'last_synced_at' => now()]);
    }
}
