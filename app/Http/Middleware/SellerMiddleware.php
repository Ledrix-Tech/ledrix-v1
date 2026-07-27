<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SellerMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if either admin or seller is logged in
        if (!Auth::guard('seller')->check() && !Auth::guard('admin')->check()) {
            return redirect(route('seller.login.get'))
                ->with('error', 'You don’t have access to the Portal !!!');
        }

        return $next($request);
    }
}
