<?php

namespace App\Http\Middleware\Central;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next, ?string $role = null)
    {
        if (! Auth::guard('super_admin')->check()) {
            return redirect()
                ->route('super-admin.login.get')
                ->with('error', 'Please login to continue.');
        }

        $admin = Auth::guard('super_admin')->user();

        if (! $admin->isActive()) {
            Auth::guard('super_admin')->logout();

            return redirect()
                ->route('super-admin.login.get')
                ->with('error', 'Your account has been deactivated.');
        }

        // Role matrix:
        // - none   → any active SA
        // - admin  → owner or admin
        // - owner  → owner only
        if ($role === 'admin' && ! $admin->isAdmin()) {
            abort(403, 'You do not have permission to access this area.');
        }

        if ($role === 'owner' && ! $admin->isOwner()) {
            abort(403, 'Only the platform owner can access this.');
        }

        if ($role && ! in_array($role, ['admin', 'owner'], true)) {
            abort(403, 'You do not have permission to access this area.');
        }

        if (! $admin->last_seen || $admin->last_seen->diffInMinutes(now()) >= 5) {
            $admin->markSeen();
        }

        return $next($request);
    }
}
