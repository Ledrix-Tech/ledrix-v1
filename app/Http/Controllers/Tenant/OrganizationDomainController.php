<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Concerns\ResolvesOrganizationTenant;
use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use App\Services\Tenant\CustomDomainVerificationService;
use App\Services\Tenant\TenantFeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OrganizationDomainController extends Controller
{
    use ResolvesOrganizationTenant;

    public function edit(CustomDomainVerificationService $domains, TenantFeatureService $features)
    {
        $tenant = $this->organizationTenant();
        $hasCustomDomain = $features->enabled('custom_domain', (int) $tenant->id);
        $hasWhiteLabel = $features->enabled('white_label', (int) $tenant->id);

        abort_unless($hasCustomDomain || $hasWhiteLabel, 403, 'Custom domain / white-label is not on your plan.');

        $verifyToken = $hasCustomDomain && $tenant->custom_domain
            ? $domains->ensureVerificationToken($tenant->fresh())
            : null;

        return $this->organizationView('domain', [
            'tenant'          => $tenant->fresh(),
            'hasCustomDomain' => $hasCustomDomain,
            'hasWhiteLabel'   => $hasWhiteLabel,
            'verifyToken'     => $verifyToken,
            'platformHost'    => $domains->platformHost(),
            'txtHost'         => $tenant->custom_domain
                ? '_ledrix-verify.'.$domains->normalize((string) $tenant->custom_domain)
                : null,
        ]);
    }

    public function updateDomain(Request $request, CustomDomainVerificationService $domains, TenantFeatureService $features)
    {
        $tenant = $this->organizationTenant();
        $features->assertEnabled('custom_domain', (int) $tenant->id);

        $validated = $request->validate([
            'custom_domain' => ['nullable', 'string', 'max:253'],
        ]);

        $raw = trim((string) ($validated['custom_domain'] ?? ''));
        if ($raw === '') {
            $tenant->forceFill([
                'custom_domain'          => null,
                'custom_domain_verified' => false,
            ])->save();

            return $this->organizationRedirect('domain', [], 'success', 'Custom domain removed.');
        }

        $domain = $domains->normalize($raw);
        if (! $domains->isValidHostname($domain)) {
            throw ValidationException::withMessages([
                'custom_domain' => 'Enter a valid hostname (e.g. crm.youragency.com).',
            ]);
        }

        $taken = \App\Models\Central\Tenant::query()
            ->where('custom_domain', $domain)
            ->where('id', '!=', $tenant->id)
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'custom_domain' => 'That domain is already in use.',
            ]);
        }

        $changed = $tenant->custom_domain !== $domain;
        $tenant->forceFill([
            'custom_domain'          => $domain,
            'custom_domain_verified' => $changed ? false : (bool) $tenant->custom_domain_verified,
        ])->save();

        $domains->ensureVerificationToken($tenant->fresh());

        $actor = Auth::guard('admin')->user() ?? Auth::guard('tenant')->user();
        AuditLog::record(
            'tenant.custom_domain_updated',
            (int) $tenant->id,
            Auth::guard('admin')->check() ? 'admin' : 'tenant',
            $actor?->id,
            $actor?->name ?? $tenant->name,
            [
                'subject_type' => 'tenant',
                'subject_id'   => $tenant->id,
                'description'  => 'Custom domain set to '.$domain,
                'after'        => ['custom_domain' => $domain],
            ]
        );

        return $this->organizationRedirect(
            'domain',
            [],
            'success',
            $changed
                ? 'Domain saved. Add DNS records below, then verify.'
                : 'Domain unchanged.'
        );
    }

    public function verifyDomain(CustomDomainVerificationService $domains, TenantFeatureService $features)
    {
        $tenant = $this->organizationTenant();
        $features->assertEnabled('custom_domain', (int) $tenant->id);

        $result = $domains->verify($tenant->fresh());

        return $this->organizationRedirect(
            'domain',
            [],
            $result['verified'] ? 'success' : 'error',
            $result['message']
        );
    }

    public function updateBranding(Request $request, TenantFeatureService $features)
    {
        $tenant = $this->organizationTenant();
        $features->assertEnabled('white_label', (int) $tenant->id);

        $validated = $request->validate([
            'logo' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_logo') && $tenant->logo) {
            if (Storage::disk('public')->exists($tenant->logo)) {
                Storage::disk('public')->delete($tenant->logo);
            }
            $tenant->forceFill(['logo' => null])->save();

            return $this->organizationRedirect('domain', [], 'success', 'Custom logo removed.');
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('tenant-logos/'.$tenant->id, 'public');
            if ($tenant->logo && Storage::disk('public')->exists($tenant->logo)) {
                Storage::disk('public')->delete($tenant->logo);
            }
            $tenant->forceFill(['logo' => $path])->save();

            return $this->organizationRedirect('domain', [], 'success', 'White-label logo updated.');
        }

        return $this->organizationRedirect('domain', [], 'error', 'Choose a logo image to upload.');
    }
}
