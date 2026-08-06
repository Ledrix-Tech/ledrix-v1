<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use App\Models\Central\SuperAdmin;
use App\Services\Security\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    public function showSetup(TotpService $totp)
    {
        /** @var SuperAdmin $admin */
        $admin = Auth::guard('super_admin')->user();

        if ($admin->two_factor_secret) {
            return view('central.pages.auth.two-factor-manage');
        }

        $secret = $totp->generateSecret();
        session(['sa_2fa_setup_secret' => $secret]);

        return view('central.pages.auth.two-factor-setup', [
            'secret' => $secret,
            'uri'    => $totp->provisioningUri($secret, $admin->email),
        ]);
    }

    public function enable(Request $request, TotpService $totp)
    {
        /** @var SuperAdmin $admin */
        $admin = Auth::guard('super_admin')->user();

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $secret = (string) session('sa_2fa_setup_secret', '');
        if ($secret === '' || ! $totp->verify($secret, $validated['code'])) {
            return back()->with('error', 'Invalid authenticator code. Try again.');
        }

        $recovery = $totp->generateRecoveryCodes();

        $admin->forceFill([
            'two_factor_secret'         => $secret,
            'two_factor_recovery_codes' => json_encode($totp->hashRecoveryCodes($recovery)),
        ])->save();

        session()->forget('sa_2fa_setup_secret');

        AuditLog::record(
            'super_admin.2fa_enabled',
            null,
            'super_admin',
            $admin->id,
            $admin->name,
        );

        return view('central.pages.auth.two-factor-recovery', [
            'codes' => $recovery,
        ]);
    }

    public function disable(Request $request, TotpService $totp)
    {
        /** @var SuperAdmin $admin */
        $admin = Auth::guard('super_admin')->user();

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
            'super_admin.2fa_disabled',
            null,
            'super_admin',
            $admin->id,
            $admin->name,
        );

        return back()->with('success', 'Two-factor authentication disabled.');
    }

    public function challenge()
    {
        if (! session('sa_2fa_pending_id')) {
            return redirect()->route('super-admin.login.get');
        }

        return view('central.pages.auth.two-factor-challenge');
    }

    public function verifyChallenge(Request $request, TotpService $totp)
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $adminId = (int) session('sa_2fa_pending_id', 0);
        $admin = SuperAdmin::query()->find($adminId);

        if (! $admin || ! $admin->isActive()) {
            session()->forget('sa_2fa_pending_id');

            return redirect()->route('super-admin.login.get')->with('error', 'Session expired. Sign in again.');
        }

        if (! $this->passesTwoFactor($admin, $validated['code'], $totp)) {
            return back()->with('error', 'Invalid authentication code.');
        }

        Auth::guard('super_admin')->login($admin, (bool) session('sa_2fa_remember', false));
        session()->forget(['sa_2fa_pending_id', 'sa_2fa_remember']);
        $request->session()->regenerate();
        $admin->markSeen();

        AuditLog::record(
            'super_admin.login',
            null,
            'super_admin',
            $admin->id,
            $admin->name,
            ['description' => 'Login with 2FA'],
        );

        return redirect()->intended(route('super-admin.index.get'))
            ->with('success', 'Welcome back, ' . $admin->name);
    }

    private function passesTwoFactor(SuperAdmin $admin, string $code, TotpService $totp): bool
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
