<?php

namespace App\Services\Tenant;

use App\Models\Central\AuditLog;
use App\Models\Central\Tenant;
use App\Models\Central\TenantEmailVerification;
use Illuminate\Support\Facades\DB;

class VerifyTenantEmailService
{
    public function verify(string $token): ?Tenant
    {
        $record = TenantEmailVerification::where('token', $token)->first();

        if (! $record || $record->isExpired()) {
            return null;
        }

        return DB::connection('central')->transaction(function () use ($record) {
            $tenant = Tenant::find($record->tenant_id);

            if (! $tenant) {
                return null;
            }

            $tenant->update([
                'email_verified_at' => now(),
                'status'            => $tenant->status === 'inactive' ? 'active' : $tenant->status,
                'last_login_at'     => $tenant->last_login_at,
            ]);

            $record->delete();

            AuditLog::record(
                action: 'tenant.email_verified',
                tenantId: $tenant->id,
                actorType: 'tenant',
                actorId: $tenant->id,
                actorName: $tenant->name,
                context: [
                    'subject_type' => 'tenant',
                    'subject_id'   => $tenant->id,
                    'description'  => 'Email address verified. Trial access enabled.',
                ]
            );

            return $tenant->fresh(['plan', 'activeMembership', 'usageSnapshot']);
        });
    }
}
