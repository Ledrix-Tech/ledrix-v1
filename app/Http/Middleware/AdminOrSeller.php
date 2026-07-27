<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOrSeller
{
    public function handle(Request $request, Closure $next)
    {
        if (auth('admin')->check() || auth('seller')->check()) {
            return $next($request);
        }

        $loginRoute = $request->is('seller*') || str_starts_with($request->path(), 'seller/')
            ? 'seller.login.get'
            : 'admin.login.get';

        return redirect()->route($loginRoute)
            ->with('error', 'Please log in first.');
    }
}
