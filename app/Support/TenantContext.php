<?php

namespace App\Support;

/**
 * Holds the active tenant ID for the current request (set by middleware later).
 */
class TenantContext
{
    protected static ?int $tenantId = null;

    public static function set(?int $tenantId): void
    {
        static::$tenantId = $tenantId;
    }

    public static function id(): ?int
    {
        return static::$tenantId;
    }

    public static function clear(): void
    {
        static::$tenantId = null;
    }

    /**
     * Resolve tenant ID from context or authenticated CRM users.
     */
    public static function resolve(): ?int
    {
        if (static::$tenantId) {
            return static::$tenantId;
        }

        if ($sessionTenantId = session('tenant_id')) {
            return (int) $sessionTenantId;
        }

        foreach (['admin', 'seller', 'client'] as $guard) {
            $user = auth($guard)->user();
            if ($user && isset($user->tenant_id)) {
                return (int) $user->tenant_id;
            }
        }

        $tenant = auth('tenant')->user();
        if ($tenant) {
            return (int) $tenant->id;
        }

        return null;
    }

    /** Fail-closed tenant resolution for CRM workspace routes and exports. */
    public static function require(): int
    {
        $tenantId = (int) (static::resolve() ?? 0);

        abort_if($tenantId <= 0, 403, 'Tenant workspace could not be resolved for this account.');

        return $tenantId;
    }
}
