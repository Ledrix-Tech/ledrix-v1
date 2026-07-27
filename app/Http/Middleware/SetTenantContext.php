<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves and sets the active tenant for the current request.
 * Enables BelongsToTenant global scopes on CRM models.
 */
class SetTenantContext
{
    public function handle(Request $request, Closure $next): Response
    {
        TenantContext::set(TenantContext::resolve());

        try {
            return $next($request);
        } finally {
            TenantContext::clear();
        }
    }
}
