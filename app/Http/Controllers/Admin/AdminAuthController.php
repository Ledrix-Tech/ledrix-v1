<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Central\Tenant;
use App\Models\Seller;
use App\Services\Tenant\ProvisionTenantAdminService;
use App\Services\Tenant\SubscriptionAccessService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AdminAuthController extends Controller
{
    public function adminLoginPage()
    {
        return view('admin.pages.auth.login');
    }
    public function adminForgotPage()
    {
        return view('admin.pages.auth.forgot');
    }
    public function adminResetPage($token)
    {
        return view('admin.pages.auth.reset', compact('token'));
    }

    public function adminLoginPost(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $throttleKey = 'admin-login:' . sha1(strtolower($credentials['email']) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->with('error', "Too many login attempts. Try again in {$seconds} seconds.");
        }

        TenantContext::clear();
        session()->forget('tenant_id');

        $admin = $this->resolveAdminForLogin($credentials['email'], $credentials['password']);

        if ($admin) {
            RateLimiter::clear($throttleKey);

            if (! $admin->tenant_id) {
                return back()->with(
                    'error',
                    'No organization workspace is linked to this admin account. Sign in via your organization portal (tenant login → Enter CRM), or contact support.'
                );
            }

            if ($admin->two_factor_secret) {
                session([
                    'admin_2fa_pending_id' => $admin->id,
                    'admin_2fa_remember'  => false,
                ]);

                return redirect()->route('admin.2fa.challenge');
            }

            Auth::guard('admin')->login($admin);
            $request->session()->regenerate();

            session(['tenant_id' => $admin->tenant_id]);
            TenantContext::set((int) $admin->tenant_id);

            $tenant = Tenant::query()->find($admin->tenant_id);
            $access = app(SubscriptionAccessService::class);

            if (! $tenant) {
                Auth::guard('admin')->logout();
                session()->forget(['tenant_id', 'role']);

                return back()->with(
                    'error',
                    'Organization workspace was not found for this account. Contact support.'
                );
            }

            if (! $access->canUseCrm($tenant)) {
                // Keep admin session so they can renew inside Admin → Billing.
                if (($admin->role ?? null) === 'admin' && $access->canAccessOrgBilling($tenant)) {
                    return redirect()
                        ->route('admin.org.billing')
                        ->with('error', 'Your subscription is not active. Renew below to restore CRM access.');
                }

                Auth::guard('admin')->logout();
                session()->forget(['tenant_id', 'role']);

                return back()->with(
                    'error',
                    'Your subscription is not active. Please renew via your organization portal, or contact support.'
                );
            }

            if ($admin->role === 'demo') {
                Auth::guard('admin')->logout();
                session()->forget(['tenant_id', 'role']);

                return redirect()
                    ->route('pricing.get')
                    ->with('info', 'Demo accounts are no longer available. Start your free 14-day trial instead.');
            }

            if ($admin->role === 'finance') {
                session(['role' => 'finance']);

                return redirect()
                    ->route('admin.brand-payments.get')
                    ->with('success', 'Login as Finance Manager Successfully !!!');
            }

            return redirect()
                ->route('admin.index.get')
                ->with('success', 'Login as Admin Successfully!');
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->with('error', 'Invalid email or password.');
    }

    /**
     * Prefer a tenant-linked admin when multiple rows share an email.
     * Orphan rows (no tenant_id) are auto-linked when a matching Tenant email exists.
     */
    protected function resolveAdminForLogin(string $email, string $password): ?Admin
    {
        $matches = Admin::withoutGlobalScopes()
            ->where('email', $email)
            ->get()
            ->filter(fn (Admin $candidate) => Hash::check($password, $candidate->password))
            ->sortByDesc(fn (Admin $candidate) => (int) (bool) $candidate->tenant_id)
            ->values();

        /** @var Admin|null $admin */
        $admin = $matches->first();

        if (! $admin) {
            return null;
        }

        if ($admin->tenant_id) {
            return $admin;
        }

        $tenant = Tenant::query()->where('email', $email)->first();
        if (! $tenant) {
            return $admin;
        }

        return app(ProvisionTenantAdminService::class)->provision($tenant);
    }

    public function adminForgotPost(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $throttleKey = 'admin-forgot:' . sha1(strtolower($request->email) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->with('error', "Too many reset requests. Try again in {$seconds} seconds.");
        }

        RateLimiter::hit($throttleKey, 300);

        // Forgot/reset runs without an authenticated tenant context — never apply BelongsToTenant scope.
        $admin  = Admin::withoutGlobalScopes()->where('email', $request->email)->first();
        $seller = Seller::withoutGlobalScopes()->where('email', $request->email)->first();

        if (! $admin && ! $seller) {
            return back()->with('success', 'If that email exists in our system, a reset link has been sent.');
        }

        $existingToken = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('created_at', '>=', Carbon::now()->subMinutes(15))
            ->first();

        if ($existingToken) {
            return back()->with('success', 'If that email exists in our system, a reset link has been sent.');
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        DB::table('password_reset_tokens')->insert([
            'email'      => $request->email,
            'token'      => Hash::make($token),
            'created_at' => Carbon::now(),
        ]);

        Mail::send('emails.admin-password', ['token' => $token], function ($message) use ($request) {
            $message->to($request->email);
            $message->subject('Reset Your Password');
        });

        return back()->with('success', 'If that email exists in our system, a reset link has been sent.');
    }


    public function adminResetPost(Request $request)
    {
        $request->validate([
            'email'     => 'required|email',
            'password'  => 'required|min:8',
            'cpassword' => 'required|same:password',
            'token'     => 'required',
        ]);

        $throttleKey = 'admin-reset:' . sha1(strtolower($request->email) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->with('error', "Too many reset attempts. Try again in {$seconds} seconds.");
        }

        $rows = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('created_at', '>=', Carbon::now()->subMinutes(30))
            ->get();

        $resetRequest = $rows->first(
            fn ($row) => Hash::check($request->token, $row->token) || hash_equals((string) $row->token, (string) $request->token)
        );

        if (! $resetRequest) {
            RateLimiter::hit($throttleKey, 60);

            return back()->with('error', 'Invalid or expired password reset token.');
        }

        // Update password in the right table (unscoped — guest reset has no tenant session).
        $admin = Admin::withoutGlobalScopes()->where('email', $request->email)->first();
        $seller = Seller::withoutGlobalScopes()->where('email', $request->email)->first();

        if ($admin) {
            $admin->forceFill(['password' => Hash::make($request->password)])->save();
        } elseif ($seller) {
            $seller->forceFill(['password' => Hash::make($request->password)])->save();
        } else {
            return back()->with('error', 'Account not found.');
        }

        RateLimiter::clear($throttleKey);
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('admin.login.get')->with('success', 'Password updated successfully!');
    }

    public function adminlogout()
    {
        Auth::guard('seller')->logout();
        Auth::guard('admin')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('admin.login.get')->with('success', 'Logout Successfully !!!');
    }
}
