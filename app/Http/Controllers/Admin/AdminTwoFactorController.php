<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Central\AuditLog;
use App\Services\Security\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminTwoFactorController extends Controller
{
    public function showSetup(TotpService $totp)
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        if ($admin->two_factor_secret) {
            return view('admin.pages.auth.two-factor-manage');
        }

        $secret = $totp->generateSecret();
        session(['admin_2fa_setup_secret' => $secret]);

        return view('admin.pages.auth.two-factor-setup', [
            'secret' => $secret,
            'uri'    => $totp->provisioningUri($secret, $admin->email, 'Ledrix CRM'),
        ]);
    }

    public function enable(Request $request, TotpService $totp)
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $secret = (string) session('admin_2fa_setup_secret', '');
        if ($secret === '' || ! $totp->verify($secret, $validated['code'])) {
            return back()->with('error', 'Invalid authenticator code. Try again.');
        }

        $recovery = $totp->generateRecoveryCodes();

        $admin->forceFill([
            'two_factor_secret'         => $secret,
            'two_factor_recovery_codes' => json_encode($totp->hashRecoveryCodes($recovery)),
        ])->save();

        session()->forget('admin_2fa_setup_secret');

        AuditLog::record(
            'admin.2fa_enabled',
            $admin->tenant_id ? (int) $admin->tenant_id : null,
            'admin',
            $admin->id,
            $admin->name,
        );

        return view('admin.pages.auth.two-factor-recovery', [
            'codes' => $recovery,
        ]);
    }

    public function disable(Request $request, TotpService $totp)
    {
        /** @var Admin $admin */
        $admin = Auth::guard('admin')->user();

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        if (! $admin->two_factor_secret || ! $this->passesTwoFactor($admin, $validated['code'], $totp)) {
            return back()->with('error', 'Invalid code.');
        }

        $admin->forceFill([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        AuditLog::record(
            'admin.2fa_disabled',
            $admin->tenant_id ? (int) $admin->tenant_id : null,
            'admin',
            $admin->id,
            $admin->name,
        );

        return back()->with('success', 'Two-factor authentication disabled.');
    }

    public function challenge()
    {
        if (! session('admin_2fa_pending_id')) {
            return redirect()->route('admin.login.get');
        }

        return view('admin.pages.auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request, TotpService $totp)
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $adminId = (int) session('admin_2fa_pending_id', 0);
        $admin = Admin::withoutGlobalScopes()->find($adminId);

        if (! $admin) {
            session()->forget(['admin_2fa_pending_id', 'admin_2fa_remember']);

            return redirect()->route('admin.login.get')->with('error', 'Session expired. Sign in again.');
        }

        if (! $this->passesTwoFactor($admin, $validated['code'], $totp)) {
            return back()->with('error', 'Invalid authentication code.');
        }

        Auth::guard('admin')->login($admin, (bool) session('admin_2fa_remember', false));
        session()->forget(['admin_2fa_pending_id', 'admin_2fa_remember']);
        $request->session()->regenerate();

        if ($admin->tenant_id) {
            session(['tenant_id' => $admin->tenant_id]);
        }

        AuditLog::record(
            'admin.login',
            $admin->tenant_id ? (int) $admin->tenant_id : null,
            'admin',
            $admin->id,
            $admin->name,
            ['description' => 'Login with 2FA'],
        );

        if (($admin->role ?? null) === 'finance') {
            session(['role' => 'finance']);

            return redirect()
                ->route('admin.brand-payments.get')
                ->with('success', 'Login as Finance Manager Successfully !!!');
        }

        return redirect()
            ->intended(route('admin.index.get'))
            ->with('success', 'Login as Admin Successfully!');
    }

    private function passesTwoFactor(Admin $admin, string $code, TotpService $totp): bool
    {
        if ($admin->two_factor_secret && $totp->verify((string) $admin->two_factor_secret, $code)) {
            return true;
        }

        $hashed = json_decode((string) $admin->two_factor_recovery_codes, true);
        if (! is_array($hashed)) {
            return false;
        }

        $remaining = $totp->consumeRecoveryCode($hashed, $code);
        if ($remaining === null) {
            return false;
        }

        $admin->forceFill([
            'two_factor_recovery_codes' => json_encode($remaining),
        ])->save();

        return true;
    }
}
