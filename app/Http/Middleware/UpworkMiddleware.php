<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpworkMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if admin is logged in and if the role is 'up_admin'
        if (!Auth::guard('admin')->check() || Auth::guard('admin')->user()->role !== 'up_admin') {
            // Redirect to login if not authenticated or if the role is not 'up_admin'
            return redirect(route('upwork.login.get'))
                ->with('error', 'You don’t have access to the Portal!!!');
        }

        return $next($request);
    }
}
