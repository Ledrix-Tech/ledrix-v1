<?php

namespace App\Http\Middleware;

use App\Services\Tenant\TenantFeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies tenant feature gates for sellers; admins bypass.
 */
class EnsurePortalTenantFeatureMiddleware
{
    public function __construct(
        private TenantFeatureService $features,
    ) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (auth('admin')->check()) {
            return $next($request);
        }

        if (auth('seller')->check()) {
            if (str_contains($feature, '|')) {
                $keys = array_filter(array_map('trim', explode('|', $feature)));
                $this->features->assertAnyEnabled($keys);
            } else {
                $this->features->assertEnabled($feature);
            }
        }

        return $next($request);
    }
}
