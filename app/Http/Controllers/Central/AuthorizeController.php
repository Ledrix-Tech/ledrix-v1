<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use App\Models\Central\SuperAdmin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthorizeController extends Controller
{
    // Max login attempts before lockout
    private int $maxAttempts = 5;
    // Lockout duration in minutes
    private int $decayMinutes = 15;


    public function adminLoginPage()
    {
        return view('central.pages.auth.login');
    }
    public function adminForgotPage()
    {
        return view('central.pages.auth.forgot');
    }
    public function adminResetPage($token)
    {
        return view('central.pages.auth.reset', compact('token'));
    }

    public function adminLoginPost(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Check rate limiter before attempting login
        $this->checkRateLimit($request);

        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        // Attempt login against super_admin guard
        if (!Auth::guard('super_admin')->attempt($credentials, $remember)) {
            // Increment failed attempts
            RateLimiter::hit($this->throttleKey($request));
            // Log failed attempt
            $this->logFailedAttempt($request);

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'These credentials do not match our records.',
                ]);
        }

        /** @var SuperAdmin $admin */
        $admin = Auth::guard('super_admin')->user();

        // Check if account is active
        if (!$admin->isActive()) {
            Auth::guard('super_admin')->logout();
            return back()->withErrors([
                'email' => 'Your account has been deactivated.',
            ]);
        }

        // Clear rate limiter on success
        RateLimiter::clear($this->throttleKey($request));

        if ($admin->two_factor_secret) {
            Auth::guard('super_admin')->logout();
            $request->session()->put('sa_2fa_pending_id', $admin->id);
            $request->session()->put('sa_2fa_remember', $remember);

            return redirect()->route('super-admin.2fa.challenge');
        }

        // Regenerate session to prevent fixation
        $request->session()->regenerate();

        // Update last seen and IP
        $admin->markSeen();

        // Audit log
        AuditLog::record(
            action: 'super_admin.login',
            actorType: 'super_admin',
            actorId: $admin->id,
            actorName: $admin->name,
        );

        return redirect()
            ->intended(route('super-admin.index.get'))
            ->with('success', 'Welcome back, ' . $admin->name);
    }

    public function adminForgotPost(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $admin = SuperAdmin::query()->where('email', $request->email)->first();
        if (! $admin) {
            // Avoid email enumeration in production responses.
            return back()->with('success', 'If that email exists, a reset code has been sent.');
        }

        $existingToken = DB::connection('central')
            ->table('super_admin_password_resets')
            ->where('email', $request->email)
            ->where('created_at', '>=', Carbon::now()->subMinutes(15))
            ->first();

        if ($existingToken) {
            return back()->with('error', 'A password reset token has already been sent in the last 15 minutes.');
        }

        $plainToken = (string) random_int(100000, 999999);

        DB::connection('central')->table('super_admin_password_resets')->where('email', $request->email)->delete();
        DB::connection('central')->table('super_admin_password_resets')->insert([
            'email'      => $request->email,
            'token'      => Hash::make($plainToken),
            'created_at' => Carbon::now(),
        ]);

        try {
            Mail::send('emails.super-admin-password', ['token' => $plainToken], function ($message) use ($request) {
                $message->to($request->email);
                $message->subject('Reset Your Password');
            });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Super admin password reset mail failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to send reset email right now. Please try again later.');
        }

        return back()->with('success', 'If that email exists, a reset code has been sent.');
    }

    public function adminResetPost(Request $request)
    {
        $request->validate([
            'email'     => 'required|email',
            'password'  => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
            ],
            'cpassword' => 'required|same:password',
            'token'     => 'required',
        ], [
            'password.regex' => 'Password must contain uppercase, lowercase, number and special character.',
        ]);

        $resetRequest = DB::connection('central')
            ->table('super_admin_password_resets')
            ->where('email', $request->email)
            ->where('created_at', '>=', Carbon::now()->subMinutes(30))
            ->first();

        if (! $resetRequest || ! Hash::check((string) $request->token, (string) $resetRequest->token)) {
            return back()->with('error', 'Invalid or expired password reset token.');
        }

        $admin = SuperAdmin::query()->where('email', $request->email)->first();
        if (! $admin) {
            return back()->with('error', 'Account not found.');
        }

        $admin->update([
            'password' => Hash::make($request->password),
        ]);

        DB::connection('central')
            ->table('super_admin_password_resets')
            ->where('email', $request->email)
            ->delete();

        return redirect()->route('super-admin.login.get')->with('success', 'Password updated successfully!');
    }

    // ── Handle Logout ──────────────────────────────────────
    public function adminlogout(Request $request)
    {
        $admin = Auth::guard('super_admin')->user();

        if ($admin) {
            AuditLog::record(
                action: 'super_admin.logout',
                actorType: 'super_admin',
                actorId: $admin->id,
                actorName: $admin->name,
            );
        }

        Auth::guard('super_admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('super-admin.login.get')
            ->with('success', 'You have been logged out.');
    }

    // ── Rate Limiting ──────────────────────────────────────
    private function checkRateLimit(Request $request): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey($request), $this->maxAttempts)) {
            $seconds = RateLimiter::availableIn($this->throttleKey($request));

            abort(429, "Too many login attempts. Please try again in {$seconds} seconds.");
        }
    }
    private function throttleKey(Request $request): string
    {
        return Str::lower($request->input('email'))
            . '|' . $request->ip();
    }
    // ── Failed Attempt Logger ──────────────────────────────
    private function logFailedAttempt(Request $request): void
    {
        AuditLog::record(
            action: 'super_admin.login_failed',
            actorType: 'system',
            context: [
                'description' => 'Failed login attempt for: ' . $request->email,
                'ip_address'  => $request->ip(),
            ]
        );
    }
}
