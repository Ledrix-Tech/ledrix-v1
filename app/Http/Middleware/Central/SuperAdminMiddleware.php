<?php

namespace App\Http\Middleware\Central;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next, string $role = null)
    {
        // Not logged in → redirect to login
        if (!Auth::guard('super_admin')->check()) {
            return redirect()
                ->route('super-admin.login.get')
                ->with('error', 'Please login to continue.');
        }

        $admin = Auth::guard('super_admin')->user();

        // Account deactivated
        if (!$admin->isActive()) {
            Auth::guard('super_admin')->logout();
            return redirect()
                ->route('super-admin.login.get')
                ->with('error', 'Your account has been deactivated.');
        }

        // Role check — usage: middleware('super_admin:owner')
        if ($role && !$admin->isAdmin()) {
            abort(403, 'You do not have permission to access this area.');
        }

        // Owner-only routes
        if ($role === 'owner' && !$admin->isOwner()) {
            abort(403, 'Only the platform owner can access this.');
        }

        // Update last seen every 5 minutes (not every request)
        if (!$admin->last_seen || $admin->last_seen->diffInMinutes(now()) >= 5) {
            $admin->markSeen();
        }

        return $next($request);
    }
}
