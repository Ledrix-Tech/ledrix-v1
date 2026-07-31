<?php

namespace App\Http\Middleware;

use App\Services\Tenant\TenantFeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies tenant feature gates for admin and seller portal routes.
 */
class EnsurePortalTenantFeatureMiddleware
{
    public function __construct(
        private TenantFeatureService $features,
    ) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (str_contains($feature, '|')) {
            $keys = array_filter(array_map('trim', explode('|', $feature)));
            $this->features->assertAnyEnabled($keys);
        } else {
            $this->features->assertEnabled($feature);
        }

        return $next($request);
    }
}
