<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\SuperAdmin;
use App\Models\Central\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class InviteController extends Controller
{
    // ── Send Invite ────────────────────────────────────────
    // Only owner can invite new admins

    public function sendInvite(Request $request)
    {
        // Only owner role can invite
        if (!Auth::guard('super_admin')->user()->isOwner()) {
            abort(403, 'Only the owner can invite new admins.');
        }

        $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique('super_admins', 'email')
                    ->using('central'),
            ],
            'role'  => ['required', Rule::in(['admin', 'support'])],
        ]);

        // Generate invite token — expires in 48 hours
        $token = Str::random(64);

        Cache::put(
            "super_admin_invite_{$token}",
            [
                'name'       => $request->name,
                'email'      => $request->email,
                'role'       => $request->role,
                'invited_by' => Auth::guard('super_admin')->id(),
            ],
            now()->addHours(48)
        );

        // Send invite email
        // Mail::to($request->email)->send(new SuperAdminInviteMail($token, $request->name));

        // Log it
        AuditLog::record(
            action:    'super_admin.invited',
            actorType: 'super_admin',
            actorId:   Auth::guard('super_admin')->id(),
            actorName: Auth::guard('super_admin')->user()->name,
            context: [
                'description' => "Invited {$request->email} as {$request->role}",
            ]
        );

        return back()->with('success', "Invite sent to {$request->email}");
    }

    // ── Show Accept Invite Page ────────────────────────────

    public function showAccept(string $token)
    {
        $invite = Cache::get("super_admin_invite_{$token}");

        if (!$invite) {
            abort(404, 'Invite link is invalid or has expired.');
        }

        return view('central.auth.accept-invite', [
            'token'  => $token,
            'invite' => $invite,
        ]);
    }

    // ── Accept Invite + Set Password ──────────────────────

    public function acceptInvite(Request $request, string $token)
    {
        $invite = Cache::get("super_admin_invite_{$token}");

        if (!$invite) {
            abort(404, 'Invite link is invalid or has expired.');
        }

        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                // Must have uppercase, lowercase, number, special char
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
            ],
        ], [
            'password.regex' => 'Password must contain uppercase, lowercase, number and special character.',
        ]);

        // Create the super admin account
        $admin = SuperAdmin::create([
            'name'     => $invite['name'],
            'email'    => $invite['email'],
            'password' => Hash::make($request->password),
            'role'     => $invite['role'],
            'status'   => 'active',
        ]);

        // Remove invite from cache
        Cache::forget("super_admin_invite_{$token}");

        // Log it
        AuditLog::record(
            action:    'super_admin.registered',
            actorType: 'super_admin',
            actorId:   $admin->id,
            actorName: $admin->name,
            context: [
                'description' => "New {$admin->role} registered via invite",
            ]
        );

        // Auto-login after accepting invite
        Auth::guard('super_admin')->login($admin);

        return redirect()
            ->route('super.dashboard')
            ->with('success', 'Welcome to Ledrix! Your account is ready.');
    }
}
