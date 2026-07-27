<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PortalAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('admin')->check()
            || Auth::guard('seller')->check()
            || Auth::guard('client')->check()) {
            return $next($request);
        }

        $loginRoute = $request->is('seller*') ? 'seller.login.get' : 'admin.login.get';

        return redirect()
            ->route($loginRoute)
            ->with('error', 'Please log in to continue.');
    }
}
