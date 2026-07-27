<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProfileDetail;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function authProfile()
    {
        $user = Auth::guard('admin')->user()
            ?? Auth::guard('seller')->user()
            ?? Auth::guard('client')->user();

        if (!$user) {
            abort(403, 'No user logged in');
        }

        // Check if profile details exist
        $profile = ProfileDetail::where('user_id', $user->id)
            ->where('user_type', get_class($user))
            ->first();

        // Split name into first/last
        $fullName = $profile->name ?? $user->name;
        $parts = explode(' ', $fullName, 2);
        $firstName = $parts[0] ?? '';
        $lastName  = $parts[1] ?? '';
        // dd($user);
        return view('admin.pages.auth.auth-profile', compact('user', 'profile', 'firstName', 'lastName'));
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
        $fileName = $profile->profile ?? null;
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
            Auth::logout();
            return redirect()->route('admin.login.get')->with('status', 'Password changed, please log in again.');
        }

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }
}
