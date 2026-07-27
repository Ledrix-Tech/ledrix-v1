<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('tenant')->check()) {
            return redirect()
                ->route('tenant.login')
                ->with('error', 'Please sign in to continue.');
        }

        $tenant = Auth::guard('tenant')->user();

        \App\Support\TenantContext::set($tenant->id);

        if (! $tenant->isEmailVerified()) {
            Auth::guard('tenant')->logout();

            return redirect()
                ->route('tenant.login')
                ->with('error', 'Please verify your email before accessing your account.');
        }

        if ($tenant->isSuspended()) {
            Auth::guard('tenant')->logout();

            return redirect()
                ->route('tenant.login')
                ->with('error', 'Your account has been suspended.');
        }

        return $next($request);
    }
}
