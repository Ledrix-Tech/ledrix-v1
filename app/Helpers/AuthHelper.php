<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('auth_admin')) {
    function auth_admin()
    {
        $admin = Auth::guard('admin')->user();
        if (!$admin) {
            // abort(302, '', ['Location' => route('admin.login.get')]);
            return back()->with('info', "You don't have access to this page");
        }
        return $admin;
    }
}

if (!function_exists('super_admin')) {
    function super_admin()
    {
        $super_admin = Auth::guard('admin')->user();
        // Redirect to login if not logged in
        if (!$super_admin) {
            // abort(302, '', ['Location' => route('admin.login.get')]);
            return back()->with('info', "You don't have access to this page");
        }
        // Only allow super admins
        if ($super_admin->role !== 'super_admin') {
            // abort(403, 'Unauthorized access. Only Super Admins can access this section.');
            return back()->with('info', "You don't have access to this page");
        }
        return $super_admin;
    }
}




if (!function_exists('currentUser')) {
    function currentUser()
    {
        return Auth::guard('admin')->user()
            ?? Auth::guard('seller')->user()
            ?? Auth::guard('client')->user();
    }
}

if (!function_exists('authRole')) {
    function authRole(): string
    {
        // 🔹 Admin or Finance
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();

            if (isset($admin->role) && $admin->role === 'finance') {
                return 'finance';
            }

            return 'admin';
        }

        // 🔹 Seller Roles
        if (Auth::guard('seller')->check()) {
            $seller = Auth::guard('seller')->user();
            return $seller->is_seller ?? 'seller';
        }

        // 🔹 Client Role
        if (Auth::guard('client')->check()) {
            return 'client';
        }

        return 'guest';
    }
}


if (!function_exists('isAdmin')) {
    function isAdmin(): bool
    {
        return authRole() === 'admin';
    }
}

if (!function_exists('isSeller')) {
    function isSeller(): bool
    {
        return authRole() === 'seller';
    }
}

if (!function_exists('isFinance')) {
    function isFinance(): bool
    {
        return authRole() === 'finance';
    }
}

if (!function_exists('isFrontSeller')) {
    function isFrontSeller(): bool
    {
        return authRole() === 'front_seller';
    }
}

if (!function_exists('isProjectManager')) {
    function isProjectManager(): bool
    {
        return authRole() === 'project_manager';
    }
}
