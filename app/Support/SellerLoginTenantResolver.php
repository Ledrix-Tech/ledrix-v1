<?php

namespace App\Support;

use App\Models\Central\Tenant;
use Illuminate\Http\Request;

final class SellerLoginTenantResolver
{
    public static function resolve(Request $request): ?int
    {
        if ($tenantId = session('tenant_id')) {
            return (int) $tenantId;
        }

        $host = strtolower($request->getHost());

        $tenant = Tenant::on('central')
            ->where('custom_domain', $host)
            ->where('custom_domain_verified', true)
            ->first();

        if ($tenant) {
            return (int) $tenant->id;
        }

        $parts = explode('.', $host);

        if (count($parts) >= 3) {
            $slug = $parts[0];

            if (! in_array($slug, ['www', 'app', 'api', 'admin', 'seller', 'mail'], true)) {
                $tenant = Tenant::on('central')->where('slug', $slug)->first();

                if ($tenant) {
                    return (int) $tenant->id;
                }
            }
        }

        return null;
    }
}
