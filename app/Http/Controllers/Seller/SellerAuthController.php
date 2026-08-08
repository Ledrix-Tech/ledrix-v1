<?php

namespace App\Http\Controllers\Seller;

use Carbon\Carbon;
use App\Models\Seller;
use App\Models\Central\Tenant;
use App\Services\Tenant\SubscriptionAccessService;
use Illuminate\Http\Request;
use App\Support\SellerLoginTenantResolver;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class SellerAuthController extends Controller
{
    public function sellerLoginPage()
    {
        return view('sellers.pages.auth.login');
    }

    public function sellerForgotPage()
    {
        return view('sellers.pages.auth.forgot');
    }

    public function sellerResetPage($token)
    {
        return view('sellers.pages.auth.reset', compact('token'));
    }

    public function sellerLoginPost(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $throttleKey = 'seller-login:' . sha1(strtolower($credentials['email']) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->with('error', "Too many login attempts. Try again in {$seconds} seconds.");
        }

        if (! isWithinWorkingHours()) {
            return view('errors.restricted');
        }

        TenantContext::clear();
        session()->forget('tenant_id');

        $candidates = Seller::withoutGlobalScopes()
            ->where('email', $credentials['email'])
            ->get();

        $matches = $candidates->filter(
            fn (Seller $candidate) => Hash::check($credentials['password'], $candidate->password)
        );

        if ($matches->isEmpty()) {
            RateLimiter::hit($throttleKey, 60);

            return back()->with('error', 'Invalid email or password.');
        }

        $tenantId = SellerLoginTenantResolver::resolve($request);

        if ($tenantId !== null) {
            $matches = $matches->where('tenant_id', $tenantId);
        }

        if ($matches->count() > 1) {
            RateLimiter::hit($throttleKey, 60);

            return back()->with('error', 'Multiple accounts match this email. Sign in using your organization CRM URL.');
        }

        if ($matches->isEmpty()) {
            RateLimiter::hit($throttleKey, 60);

            return back()->with('error', 'Invalid email or password for this organization.');
        }

        $seller = $matches->first();

        if ($seller) {
            if ($seller->status !== 'Active') {
                return back()->with('error', 'Your account is inactive. Please contact support.');
            }

            RateLimiter::clear($throttleKey);
            Auth::guard('seller')->login($seller);
            $request->session()->regenerate();

            if ($seller->tenant_id) {
                session(['tenant_id' => $seller->tenant_id]);
                TenantContext::set($seller->tenant_id);

                $tenant = Tenant::query()->find($seller->tenant_id);
                if ($tenant && ! app(SubscriptionAccessService::class)->canUseCrm($tenant)) {
                    Auth::guard('seller')->logout();
                    session()->forget(['tenant_id', 'role']);

                    return back()->with(
                        'error',
                        'Your organization subscription is not active. Ask your administrator to renew billing in Admin → Organization → Billing.'
                    );
                }
            }

            session(['role' => $seller->is_seller]);

            return redirect()
                ->route('seller.index.get')
                ->with('success', 'Welcome ' . $seller->name);
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->with('error', 'Invalid email or password.');
    }

    public function sellerForgotPost(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $throttleKey = 'seller-forgot:' . sha1(strtolower($request->email) . '|' . $request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->with('error', "Too many reset requests. Try again in {$seconds} seconds.");
        }

        RateLimiter::hit($throttleKey, 300);

        $tenantId = SellerLoginTenantResolver::resolve($request);

        $sellerQuery = Seller::withoutGlobalScopes()->where('email', $request->email);

        if ($tenantId !== null) {
            $sellerQuery->where('tenant_id', $tenantId);
        }

        $sellers = $sellerQuery->get();

        if ($sellers->count() !== 1) {
            return back()->with('success', 'If that email exists in our system, a reset link has been sent.');
        }

        $seller = $sellers->first();

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

        Mail::send('emails.seller-password', ['token' => $token], function ($message) use ($request) {
            $message->to($request->email);
            $message->subject('Reset Your Password');
        });

        return back()->with('success', 'If that email exists in our system, a reset link has been sent.');
    }

    public function sellerResetPost(Request $request)
    {
        $request->validate([
            'email'     => 'required|email',
            'password'  => 'required|min:8',
            'cpassword' => 'required|same:password',
            'token'     => 'required',
        ]);

        $throttleKey = 'seller-reset:' . sha1(strtolower($request->email) . '|' . $request->ip());

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

        $tenantId = SellerLoginTenantResolver::resolve($request);

        $sellerQuery = Seller::withoutGlobalScopes()->where('email', $request->email);

        if ($tenantId !== null) {
            $sellerQuery->where('tenant_id', $tenantId);
        }

        $seller = $sellerQuery->first();

        if (! $seller) {
            return back()->with('error', 'Account not found.');
        }

        $seller->password = Hash::make($request->password);
        $seller->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        RateLimiter::clear($throttleKey);

        return redirect()->route('seller.login.get')->with('success', 'Password updated successfully!');
    }

    public function sellerlogout(Request $request)
    {
        $seller = Auth::guard('seller')->user();

        if ($seller) {
            foreach (session()->all() as $key => $val) {
                if (str_starts_with($key, 'viewed_lead_')) {
                    session()->forget($key);
                }
            }

            Auth::guard('seller')->logout();
        }

        if (auth('admin')->check()) {
            Auth::guard('admin')->logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('seller.login.get')->with('success', 'Logout Successfully !!!');
    }
}
