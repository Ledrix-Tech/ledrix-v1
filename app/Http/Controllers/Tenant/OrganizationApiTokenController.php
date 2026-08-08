<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use App\Models\Central\TenantApiToken;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrganizationApiTokenController extends Controller
{
    use ResolvesOrganizationTenant;

    public function index(TenantFeatureService $features)
    {
        $tenant = $this->organizationTenant();
        $features->assertEnabled('api_access', (int) $tenant->id);

        $apiTokens = TenantApiToken::query()
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->get();

        return $this->organizationView('api-tokens', [
            'tenant'    => $tenant,
            'apiTokens' => $apiTokens,
        ]);
    }

    public function store(Request $request, TenantFeatureService $features)
    {
        $tenant = $this->organizationTenant();
        $features->assertEnabled('api_access', (int) $tenant->id);

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'abilities'  => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', 'date', 'after:today'],
        ]);

        $abilities = ['*'];
        if (! empty($validated['abilities'])) {
            $abilities = collect(explode(',', $validated['abilities']))
                ->map(fn ($a) => trim($a))
                ->filter()
                ->values()
                ->all() ?: ['*'];
        }

        [$plain, $record] = TenantApiToken::generate((int) $tenant->id, $validated['name'], $abilities);

        if (! empty($validated['expires_at'])) {
            $record->update(['expires_at' => $validated['expires_at']]);
        }

        $actor = Auth::guard('admin')->user() ?? Auth::guard('tenant')->user();

        AuditLog::record(
            'tenant.api_token_created',
            (int) $tenant->id,
            Auth::guard('admin')->check() ? 'admin' : 'tenant',
            $actor?->id,
            $actor?->name ?? $tenant->name,
            [
                'subject_type' => 'tenant_api_token',
                'subject_id'   => $record->id,
                'description'  => "Created API token \"{$record->name}\"",
            ]
        );

        return $this->organizationRedirect('api-tokens', [], 'success', 'API token created. Copy it now — it will not be shown again.')
            ->with('new_api_token', $plain);
    }

    public function revoke(int $id, TenantFeatureService $features)
    {
        $tenant = $this->organizationTenant();
        $features->assertEnabled('api_access', (int) $tenant->id);

        $token = TenantApiToken::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($id);

        if ($token->status === 'revoked') {
            return $this->organizationRedirect('api-tokens', [], 'error', 'Token is already revoked.');
        }

        $token->revoke();

        $actor = Auth::guard('admin')->user() ?? Auth::guard('tenant')->user();

        AuditLog::record(
            'tenant.api_token_revoked',
            (int) $tenant->id,
            Auth::guard('admin')->check() ? 'admin' : 'tenant',
            $actor?->id,
            $actor?->name ?? $tenant->name,
            [
                'subject_type' => 'tenant_api_token',
                'subject_id'   => $token->id,
                'description'  => "Revoked API token \"{$token->name}\"",
            ]
        );

        return $this->organizationRedirect('api-tokens', [], 'success', 'API token revoked.');
    }
}
