<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Central\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('front.pages.tenant.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $tenant = Tenant::where('email', $credentials['email'])->first();

        if (! $tenant || ! Hash::check($credentials['password'], $tenant->password)) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Invalid email or password.');
        }

        if (! $tenant->isEmailVerified()) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Please verify your email before signing in.');
        }

        if ($tenant->isSuspended()) {
            return back()->with('error', 'Your account has been suspended. Contact support.');
        }

        if ($tenant->isCancelled()) {
            return back()->with('error', 'Your account has been cancelled.');
        }

        Auth::guard('tenant')->login($tenant, $request->boolean('remember'));

        $tenant->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return redirect()
            ->route('tenant.dashboard')
            ->with('success', 'Welcome back!');
    }

    public function logout()
    {
        Auth::guard('tenant')->logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()
            ->route('tenant.login')
            ->with('success', 'You have been logged out.');
    }
}
