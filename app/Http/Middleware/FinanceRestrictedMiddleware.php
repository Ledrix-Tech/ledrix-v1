<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class FinanceRestrictedMiddleware
{
    /** @var list<string> */
    private const FINANCE_ALLOWED_ROUTES = [
        'admin.brand-payments.get',
        'admin.brand-payouts.get',
        'admin.logout',
        'auth.profile.get',
        'auth.profile.update',
        'admin.2fa.setup',
        'admin.2fa.enable',
        'admin.2fa.disable',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            return $next($request);
        }

        $admin = Auth::guard('admin')->user();

        if (($admin->role ?? null) !== 'finance') {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName && in_array($routeName, self::FINANCE_ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        return redirect()
            ->route('admin.brand-payments.get')
            ->with('info', 'Finance accounts can only access brand payment reports.');
    }
}
