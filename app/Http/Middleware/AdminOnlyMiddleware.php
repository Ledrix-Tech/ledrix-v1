<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminOnlyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            return redirect()
                ->route('admin.login.get')
                ->with('error', 'Administrator login is required for this page.');
        }

        $admin = Auth::guard('admin')->user();

        if (($admin->role ?? null) === 'finance') {
            return redirect()
                ->route('admin.brand-payments.get')
                ->with('info', 'Payment account keys are managed by administrators only.');
        }

        return $next($request);
    }
}
