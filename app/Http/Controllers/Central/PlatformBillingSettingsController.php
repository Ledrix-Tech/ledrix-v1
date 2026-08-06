<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Services\Billing\PlatformBillingSettingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlatformBillingSettingsController extends Controller
{
    public function edit(PlatformBillingSettingsService $settings)
    {
        if (! $settings->tableReady()) {
            return view('central.pages.billing-settings', [
                'providers'         => [],
                'definitions'       => $settings->definitions(),
                'migrationRequired' => true,
            ]);
        }

        return view('central.pages.billing-settings', [
            'providers'         => $settings->allForAdmin(),
            'definitions'       => $settings->definitions(),
            'migrationRequired' => false,
        ]);
    }

    public function update(Request $request, string $provider, PlatformBillingSettingsService $settings)
    {
        abort_unless(in_array($provider, PlatformBillingSettingsService::PROVIDERS, true), 404);

        if (! $settings->tableReady()) {
            return back()->with(
                'error',
                'Run central migrations first: php artisan migrate --database=central --path=database/migrations/central --force'
            );
        }

        $fieldKeys = array_keys($settings->definitions()[$provider]['fields'] ?? []);

        $rules = [
            'enabled' => ['nullable', 'boolean'],
        ];

        foreach ($fieldKeys as $key) {
            $rules["credentials.{$key}"] = ['nullable', 'string', 'max:500'];
        }

        $validated = $request->validate($rules);
        $credentials = $validated['credentials'] ?? [];

        $settings->update(
            provider: $provider,
            enabled: $request->boolean('enabled'),
            credentials: $credentials,
            updatedBy: Auth::guard('super_admin')->id(),
        );

        $label = $settings->definitions()[$provider]['label'] ?? ucfirst($provider);

        return back()->with('success', "{$label} settings saved.");
    }
}
