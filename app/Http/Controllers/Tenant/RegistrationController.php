<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\RegisterTenantRequest;
use App\Models\Central\PackagePricing;
use App\Models\Central\TenantEmailVerification;
use App\Services\Tenant\RegisterTenantService;
use App\Services\Tenant\VerifyTenantEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\TenantVerifyEmail;

class RegistrationController extends Controller
{
    public function showForm(string $slug)
    {
        $package = PackagePricing::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->where('is_public', true)
            ->firstOrFail();

        return view('front.pages.tenant.register', compact('package'));
    }

    public function store(
        RegisterTenantRequest $request,
        RegisterTenantService $registerTenantService
    ) {
        $result = $registerTenantService->register($request->validated());

        $payload = [
            'email' => $result['tenant']->email,
            'plan'  => $result['tenant']->plan?->name,
        ];

        if (config('app.debug')) {
            $payload['verify_url'] = route('tenant.verify-email', [
                'token' => $result['verification']->token,
            ]);
        }

        return redirect()
            ->route('tenant.register.success')
            ->with($payload);
    }

    public function success()
    {
        if (! session('email')) {
            return redirect()->route('pricing.get');
        }

        return view('front.pages.tenant.register-success', [
            'email'     => session('email'),
            'plan'      => session('plan'),
            'verifyUrl' => session('verify_url'),
        ]);
    }

    public function verify(string $token, VerifyTenantEmailService $verifyTenantEmailService)
    {
        $tenant = $verifyTenantEmailService->verify($token);

        if ($tenant) {
            auth('tenant')->login($tenant);

            $tenant->update([
                'last_login_at' => now(),
                'last_login_ip' => request()->ip(),
            ]);

            return redirect()
                ->route('tenant.dashboard')
                ->with('success', 'Email verified! Your free trial is now active.');
        }

        return view('front.pages.tenant.verify-email', [
            'success' => false,
            'tenant'  => null,
        ]);
    }

    public function resend(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $tenant = \App\Models\Central\Tenant::where('email', $request->email)->first();

        if (! $tenant || $tenant->isEmailVerified()) {
            return back()->with('success', 'If an unverified account exists, a new link has been sent.');
        }

        $verification = TenantEmailVerification::generate(
            tenantId: $tenant->id,
            email: $tenant->email,
        );

        $verifyUrl = route('tenant.verify-email', ['token' => $verification->token]);

        try {
            Mail::to($tenant->email)->send(new TenantVerifyEmail($tenant, $verifyUrl, $tenant->plan));
        } catch (\Throwable) {
            return back()->with('error', 'Unable to send verification email. Please try again later.');
        }

        return back()->with('success', 'Verification email sent. Please check your inbox.');
    }
}
