<?php

namespace App\Http\Controllers\API\Client;

use Carbon\Carbon;
use App\Models\Client;
use Illuminate\Http\Request;
use App\Models\ProfileDetail;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ApiAuthController extends Controller
{
    public function clientLoginPost(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $client = Client::where('email', $credentials['email'])->first();

        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'No account found with this email.'
            ], 404);
        }

        if ($client->status !== 'Active') {
            return response()->json([
                'status' => false,
                'message' => 'Your account is inactive. Please contact support.'
            ], 403);
        }

        if (!Auth::guard('client')->attempt($credentials)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials.'
            ], 401);
        }

        // generate sanctum token (stateless)
        $token = $client->createToken('client-api-token')->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Login successful!',
            'token'   => $token,
            'data'    => $client
        ]);
    }



    public function clientForgotPost(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:clients,email',
        ]);

        $existingToken = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('created_at', '>=', Carbon::now()->subMinutes(15))
            ->first();

        if ($existingToken) {
            return response()->json([
                'status'  => false,
                'message' => 'A password reset token has already been sent in the last 15 minutes.'
            ], 429);
        }

        $token = mt_rand(100000, 999999);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        DB::table('password_reset_tokens')->insert([
            'email'      => $request->email,
            'token'      => $token,
            'created_at' => Carbon::now()
        ]);
        // dd($existingToken,$token);

        Mail::send('emails.emp-password', ['token' => $token], function ($message) use ($request) {
            $message->to($request->email);
            $message->subject('Reset Your Password');
        });

        return response()->json([
            'status'  => true,
            'message' => 'Password reset code sent! Please check your email.'
        ]);
    }



    public function clientResetPost(Request $request)
    {
        $request->validate([
            'email'     => 'required|email|exists:clients,email',
            'password'  => 'required|min:8|confirmed',
            'token'     => 'required'
        ]);

        $resetRequest = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->where('created_at', '>=', Carbon::now()->subMinutes(30))
            ->first();

        if (!$resetRequest) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired password reset token.'
            ], 400);
        }

        Client::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Password updated successfully!'
        ]);
    }


    public function clientLogout(Request $request)
    {
        // revoke the current token only
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Logout successful!'
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user(); // Authenticated via Sanctum

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'No user logged in.'
            ], 401);
        }

        $request->validate([
            'first_name'       => 'nullable|string|max:255',
            'last_name'        => 'nullable|string|max:255',
            'email'            => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients', 'email')->ignore($user->id),
            ],
            'alternate_email'  => 'nullable|email|max:255',
            'phone'            => 'nullable|string|max:20',
            'address'          => 'nullable|string|max:500',
            'profile'          => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
            'password'         => 'nullable|min:8|confirmed', // expects password_confirmation field
        ]);

        $altEmail = $request->input('alternate_email')
            ? strtolower(trim($request->input('alternate_email')))
            : null;

        // Handle profile picture upload
        $fileName = $user->profile; // keep existing if not updated
        if ($request->hasFile('profile')) {
            $file = $request->file('profile');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/profiles/'), $fileName);

            // Delete old profile pic if exists
            if (!empty($user->profile) && file_exists(public_path('uploads/profiles/' . $user->profile))) {
                @unlink(public_path('uploads/profiles/' . $user->profile));
            }
        }

        // Update main client record
        $fullName   = trim($request->input('first_name') . ' ' . $request->input('last_name'));
        $user->name = $fullName ?: $user->name;

        if ($request->filled('email') && $request->email !== $user->email) {
            $user->email = strtolower(trim($request->email));
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->profile = $fileName;
        $user->save();

        // Update or create profile detail record
        $profile = ProfileDetail::updateOrCreate(
            [
                'user_id'   => $user->id,
                'user_type' => get_class($user),
            ],
            [
                'name'            => $user->name,
                'email'           => $user->email,
                'alternate_email' => $altEmail,
                'phone'           => $request->input('phone'),
                'address'         => $request->input('address'),
                'profile'         => $fileName,
            ]
        );

        // If password changed → revoke token (force re-login)
        if ($request->filled('password')) {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Password changed, please log in again.'
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Profile updated successfully!',
            'data'    => [
                'user'    => $user,
                'profile' => $profile
            ]
        ]);
    }
}
