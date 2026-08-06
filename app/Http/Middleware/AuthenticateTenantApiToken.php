<?php

namespace App\Http\Middleware;

use App\Models\Central\TenantApiToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateTenantApiToken
{
    public function handle(Request $request, Closure $next, string ...$abilityParts): Response
    {
        $ability = $abilityParts === [] ? '*' : implode(':', $abilityParts);

        $plain = $request->bearerToken()
            ?: $request->header('X-Api-Token')
            ?: $request->query('api_token');

        if (! is_string($plain) || $plain === '') {
            return response()->json(['success' => false, 'error' => 'Missing API token.'], 401);
        }

        $token = TenantApiToken::findByPlainToken($plain);

        if (! $token || ! $token->isValid()) {
            return response()->json(['success' => false, 'error' => 'Invalid or revoked API token.'], 401);
        }

        if ($ability !== '*' && ! $token->can($ability) && ! $token->can('*')) {
            return response()->json(['success' => false, 'error' => 'Token lacks required ability.'], 403);
        }

        $tenant = $token->tenant;
        if (! $tenant || $tenant->status !== 'active') {
            return response()->json(['success' => false, 'error' => 'Tenant is not active.'], 403);
        }

        $token->recordUsage();

        $request->attributes->set('api_token', $token);
        $request->attributes->set('company', $tenant);

        return $next($request);
    }
}
