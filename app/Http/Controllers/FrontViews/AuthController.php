<?php

namespace App\Http\Controllers\FrontViews;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Central\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function showSignUp()
    {
        return view('front.pages.auth.register');
    }

    public function showSignIn()
    {
        return view('front.pages.auth.login');
    }

    public function storePassword(Request $req, Tenant $company)
    {
        $req->validate(['password' => 'required|string|min:8|confirmed']);
        $company->password = Hash::make($req->password);
        $company->must_change_password = false; // optional flag
        $company->password_set_at = now();
        $company->save();
        // optional: log them in or redirect to login
        return redirect()->route('company-login.get')->with('success', 'Password set. You can now login.');
    }


    public function companyLoginPost(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        // Find company in Super DB
        $company = Tenant::where('email', $request->email)->first();
        if (!$company) {
            return back()->with('error', '❌ Email not found in records!');
        }
        // Verify password
        if (!Hash::check($request->password, $company->password)) {
            return back()->with('error', '❌ Invalid password!');
        }
        // Login manually using guard
        Auth::guard('companies')->login($company);
        return redirect()->route('company-profile.get')
            ->with('success', 'Login Successfully!');
    }

    // Company forgot password
    public function companyForgotPost(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $company = Tenant::where('email', $request->email)->first();

        if (!$company) {
            return back()->with('error', 'Email not found in our records.');
        }

        // Prevent multiple tokens within 15 minutes
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
        // Mail::send('emails.company-password', ['token' => $token], function ($message) use ($request) {
        //     $message->to($request->email);
        //     $message->subject('Reset Your Password');
        // });

        return back()->with('success', 'Password reset code sent! Please check your email.');
    }

    // Company reset password
    public function companyResetPost(Request $request)
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

        Tenant::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('company-login.get')->with('success', 'Password updated successfully!');
    }

    // Company logout
    public function companylogout()
    {
        Auth::guard('companies')->logout();
        session()->flush();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('company-login.get')->with('success', 'Logout Successfully!');
    }

   
}
