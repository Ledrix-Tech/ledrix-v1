<?php

namespace App\Http\Controllers\API\Client;

use App\Support\ClientPortalAuthorization;
use App\Support\TenantContext;
use App\Services\Tenant\TenantFeatureService;
use Carbon\Carbon;
use App\Models\Client;
use Illuminate\Http\Request;
use App\Models\ProfileDetail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private TenantFeatureService $tenantFeatures,
    ) {}

    public function clientLoginPage()
    {
        return view('clients.pages.auth.login');
    }
    public function clientForgotPage()
    {
        return view('clients.pages.auth.forgot');
    }
    public function clientResetPage($token)
    {
        return view('clients.pages.auth.reset', compact('token'));
    }

    public function clientLoginPost(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $email = strtolower(trim($credentials['email']));
        $query = Client::withoutGlobalScopes()->where('email', $email);

        if ($tenantId = TenantContext::resolve()) {
            $query->where('tenant_id', $tenantId);
        }

        $matches = $query->get();

        if ($matches->isEmpty()) {
            return back()->with('error', 'No account found with this email.');
        }

        if ($matches->count() > 1 && ! TenantContext::resolve()) {
            return back()->with('error', 'Multiple accounts share this email. Please contact support to sign in.');
        }

        $client = $matches->first();

        if ($client->status !== 'Active') {
            return back()->with('error', 'Your account is inactive. Please contact support.');
        }

        if (! $client->hasPortalAccess()) {
            return back()->with('error', 'Portal access has not been enabled for your account. Contact your account manager.');
        }

        if (! Hash::check($credentials['password'], $client->password)) {
            return back()->with('error', 'Invalid credentials.');
        }

        Auth::guard('client')->login($client);
        session([
            'role' => 'client',
            'tenant_id' => $client->tenant_id,
        ]);

        if (session()->has('redirect_to_brief')) {
            $redirectUrl = session('redirect_to_brief');
            session()->forget('redirect_to_brief');

            return redirect($redirectUrl)->with('success', 'Login Successfully! You are now redirected to the brief form.');
        }

        return redirect()->route('client.index.get')->with('success', 'Login Successfully!');
    }

    public function clientForgotPost(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $client = $this->findEligiblePortalClient($request->email);

        if (! $client) {
            return back()->with('error', 'No eligible client portal account found for this email.');
        }

        // Check existing token within last 15 minutes
        $existingToken = DB::table('password_reset_tokens')
            ->where('email', $client->email)
            ->where('created_at', '>=', Carbon::now()->subMinutes(15))
            ->first();

        if ($existingToken) {
            return back()->with('error', 'A password reset token has already been sent in the last 15 minutes.');
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->where('email', $client->email)->delete();

        DB::table('password_reset_tokens')->insert([
            'email'      => $client->email,
            'token'      => $token,
            'created_at' => Carbon::now(),
        ]);

        Mail::send('emails.client-password', ['token' => $token], function ($message) use ($client) {
            $message->to($client->email);
            $message->subject('Reset Your Password!');
        });

        return back()->with('success', 'Password reset code sent! Please check your email.');
    }

    public function clientResetPost(Request $request)
    {
        $request->validate([
            'email'     => 'required|email',
            'password'  => 'required|min:8',
            'cpassword' => 'required|same:password',
            'token'     => 'required',
        ]);

        $resetRequest = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->first();

        if (! $resetRequest) {
            return back()->with('error', 'Invalid or expired password reset token.');
        }

        $client = $this->findEligiblePortalClient($request->email);

        if (! $client) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return back()->with('error', 'This account is not eligible for client portal access.');
        }

        $client->password = $request->password;
        $client->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return redirect()->route('client.login.get')->with('success', 'Password updated successfully!');
    }

    private function findEligiblePortalClient(string $email): ?Client
    {
        $email = strtolower(trim($email));
        $query = Client::withoutGlobalScopes()->where('email', $email);

        if ($tenantId = TenantContext::resolve()) {
            $query->where('tenant_id', $tenantId);
        }

        $client = $query->first();

        if (! $client || $client->status !== 'Active' || ! $client->hasPortalAccess()) {
            return null;
        }

        if ($client->tenant_id && ! $this->tenantFeatures->enabled('client_portal', (int) $client->tenant_id)) {
            return null;
        }

        return $client;
    }


    public function clientlogout()
    {
        Auth::guard('client')->logout();
        session()->forget(['tenant_id', 'role']);

        return redirect()->route('client.login.get')->with('success', 'Logout Successfully!');
    }

    private function activeGuard(): ?string
    {
        foreach (['admin', 'seller', 'client'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }

    private function loginRouteForGuard(?string $guard): string
    {
        return match ($guard) {
            'client' => 'client.login.get',
            'seller' => 'seller.login.get',
            default  => 'admin.login.get',
        };
    }

    public function updateProfile(Request $request)
    {
        // Detect logged-in guard
        $user = Auth::guard('admin')->user()
            ?? Auth::guard('seller')->user()
            ?? Auth::guard('client')->user();

        if (!$user) {
            return redirect()->back()->with('error', 'No user logged in');
        }
        $request->validate([
            'first_name'      => 'nullable|string|max:255',
            'last_name'       => 'nullable|string|max:255',
            'email'           => 'nullable|email|max:255|unique:admins,email,' . $user->id
                . '|unique:sellers,email,' . $user->id
                . '|unique:clients,email,' . $user->id,
            'alternate_email' => 'nullable|email|max:255',
            'phone'           => 'nullable|string|max:20',
            'address'         => 'nullable|string|max:500',
            'profile'         => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'password'        => 'nullable|min:8',
            'confirm_password' => 'nullable|min:8|same:password',
        ]);
        // Normalize email to lowercase
        $user->email = strtolower(trim($user->email));
        $altEmail = $request->input('alternate_email') ? strtolower(trim($request->input('alternate_email'))) : null;
        // Fetch existing profile record
        $profile = ProfileDetail::where('user_id', $user->id)
            ->where('user_type', get_class($user))
            ->first();
        $fileName = $profile?->profile;
        // Handle profile image upload
        if ($request->hasFile('profile')) {
            // Delete old image if exists
            if ($fileName && file_exists(public_path('uploads/profiles/' . $fileName))) {
                @unlink(public_path('uploads/profiles/' . $fileName));
            }
            // Save new image
            $file = $request->file('profile');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/profiles/'), $fileName);
        }
        // Build full name
        $fullName = trim($request->input('first_name') . ' ' . $request->input('last_name'));

        /** Update Guard Table (admins / sellers / clients) */
        $user->name  = $fullName ?: $user->name;
        if ($request->filled('email') && $request->email !== $user->email) {
            $user->email = $request->email;
        }
        // if ($request->filled('phone') && $request->phone !== $user->phone) {
        //     $user->phone = $request->phone;
        // }
        if ($request->filled('password')) {
            $user->password = $request->password;
        }
        $user->save();

        /** Sync ProfileDetails Table */
        $profile = ProfileDetail::updateOrCreate(
            [
                'user_id'   => $user->id,
                'user_type' => get_class($user),
            ],
            [
                'name'            => $user->name,
                'email'           => $user->email,
                'alternate_email' => $request->input('alternate_email'),
                'phone'           => $request->input('phone'),
                'address'         => $request->input('address'),
                'profile'         => $fileName,
            ]
        );
        /** Two-way Sync: Ensure ProfileDetail stays in sync */
        if ($profile->wasChanged(['name', 'email'])) {
            $user->name  = $profile->name;
            $user->email = $profile->email;
            $user->save();
        }
        // Logout if password changed
        if ($request->filled('password')) {
            $guard = $this->activeGuard() ?? 'admin';
            Auth::guard($guard)->logout();
            session()->forget(['tenant_id', 'role']);

            return redirect()
                ->route($this->loginRouteForGuard($guard))
                ->with('status', 'Password changed, please log in again.');
        }

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }
}
