<?php

namespace App\Http\Controllers\Upwork;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function upworkLoginPage()
    {
        return view('upwork.pages.auth.login');
    }
    public function upworkForgotPage()
    {
        return view('upwork.pages.auth.forgot');
    }
    public function upworkResetPage($token)
    {
        return view('upwork.pages.auth.reset', compact('token'));
    }

    public function upworkLoginPost(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!isWithinWorkingHours()) {
            // return redirect()->route('upwork.login.get')->with('error', 'Login allowed only during working hours or from office.');
            return view('errors.restricted');
        }

        TenantContext::clear();
        session()->forget('tenant_id');

        // Unscoped lookup — guest login must ignore leftover BelongsToTenant session.
        $admin = Admin::withoutGlobalScopes()
            ->where('email', $credentials['email'])
            ->get()
            ->first(fn (Admin $candidate) => Hash::check($credentials['password'], $candidate->password));

        if ($admin) {
            Auth::guard('admin')->login($admin);
            $request->session()->regenerate();
            if ($admin->tenant_id) {
                session(['tenant_id' => $admin->tenant_id]);
                TenantContext::set((int) $admin->tenant_id);
            }
            session(['role' => 'up_admin']);

            return redirect()->route('upwork.index.get')->with('success', 'Login as Admin Successfully !!!');
        }

        return back()->with('error', '❌ Record not matched with data !!!');
    }

    public function upworkForgotPost(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $admin = Admin::withoutGlobalScopes()->where('email', $request->email)->first();
        if (!$admin) {
            return back()->with('error', 'Email not found in our records.');
        }

        // Prevent spam: existing token in last 15 minutes
        $existingToken = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('created_at', '>=', Carbon::now()->subMinutes(15))
            ->first();

        if ($existingToken) {
            return back()->with('error', 'A password reset token has already been sent in the last 15 minutes.');
        }

        $token = mt_rand(100000, 999999);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        DB::table('password_reset_tokens')->insert([
            'email'      => $request->email,
            'token'      => $token,
            'created_at' => Carbon::now(),
        ]);

        // Send email
        Mail::send('emails.upwork-password', ['token' => $token], function ($message) use ($request) {
            $message->to($request->email);
            $message->subject('Reset Your Password');
        });

        return back()->with('success', 'Password reset code sent! Please check your email.');
    }


    public function upworkResetPost(Request $request)
    {
        $request->validate([
            'email'     => 'required|email',
            'password'  => 'required|min:8',
            'cpassword' => 'required|same:password',
            'token'     => 'required'
        ]);

        $resetRequest = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->where('created_at', '>=', Carbon::now()->subMinutes(30)) // 30 min expiry
            ->first();

        if (!$resetRequest) {
            return back()->with('error', 'Invalid or expired password reset token.');
        }

        $admin = Admin::withoutGlobalScopes()->where('email', $request->email)->first();
        if (! $admin) {
            return back()->with('error', 'Account not found.');
        }

        $admin->forceFill(['password' => Hash::make($request->password)])->save();
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('upwork.login.get')->with('success', 'Password updated successfully!');
    }

    public function upworklogout()
    {
        // $seller = Auth::guard('seller')->user();

        // if ($seller) {
        //     // Clear viewed leads from session
        //     foreach (session()->all() as $key => $val) {
        //         if (str_starts_with($key, 'viewed_lead_')) {
        //             session()->forget($key);
        //         }
        //     }
        //     // Append logout event (instead of deleting old entries)
        //     $logFile = storage_path('logs/lead-views.log');
        //     $logoutEntry = json_encode([
        //         'seller_id' => $seller->id,
        //         'Username' => $seller->name,
        //         'action' => 'logout',
        //         'timestamp' => now()->toDateTimeString(),
        //     ]);
        //     file_put_contents($logFile, $logoutEntry . PHP_EOL, FILE_APPEND | LOCK_EX);
        //     // Clear session and logout
        //     session()->flush();
        //     Auth::guard('seller')->logout();
        // }

        Auth::guard('admin')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('upwork.login.get')->with('success', 'Logout Successfully !!!');
    }
}
