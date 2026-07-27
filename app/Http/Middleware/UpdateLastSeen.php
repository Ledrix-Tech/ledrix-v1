<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        foreach (['super_admin', 'admin', 'seller', 'client'] as $guard) {
            if (! Auth::guard($guard)->check()) {
                continue;
            }

            $user = Auth::guard($guard)->user();
            if (! $user || ! isset($user->last_seen)) {
                break;
            }

            // Avoid a DB write on every asset/request — update at most once per minute
            if ($user->last_seen && $user->last_seen->gt(now()->subMinute())) {
                break;
            }

            $user->last_seen = now();
            $user->saveQuietly();
            break;
        }

        return $next($request);
    }
}
