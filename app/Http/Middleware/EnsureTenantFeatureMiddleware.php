<?php

namespace App\Http\Middleware;

use App\Services\Tenant\TenantFeatureService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantFeatureMiddleware
{
    public function __construct(
        private TenantFeatureService $features,
    ) {}

    /**
     * @param  string  $feature  Feature key, or OR-list e.g. "stripe|paypal"
     */
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
