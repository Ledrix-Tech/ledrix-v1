<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Mail\SuperAdminInviteMail;
use App\Models\Central\AuditLog;
use App\Models\Central\SuperAdmin;
use App\Models\Central\SuperAdminInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class InviteController extends Controller
{
    public function sendInvite(Request $request)
    {
        if (! Auth::guard('super_admin')->user()?->isOwner()) {
            abort(403, 'Only the owner can invite new admins.');
        }

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                Rule::unique(SuperAdmin::class, 'email'),
            ],
            'role'  => ['required', Rule::in(['admin', 'support'])],
        ]);

        // Replace any unused prior invite for this email.
        SuperAdminInvite::query()
            ->where('email', $validated['email'])
            ->whereNull('accepted_at')
            ->delete();

        $invite = SuperAdminInvite::issue(
            $validated,
            Auth::guard('super_admin')->id(),
        );

        $acceptUrl = route('super-admin.invite.accept', ['token' => $invite->token]);

        try {
            Mail::to($validated['email'])->send(
                new SuperAdminInviteMail($invite->token, $validated['name'], $validated['role'])
            );
            $mailNote = 'Invite email sent.';
        } catch (Throwable $e) {
            Log::warning('Super admin invite mail failed', [
                'email' => $validated['email'],
                'error' => $e->getMessage(),
            ]);
            $mailNote = 'Invite created (email failed). Share this link: ' . $acceptUrl;
        }

        AuditLog::record(
            'super_admin.invited',
            null,
            'super_admin',
            Auth::guard('super_admin')->id(),
            Auth::guard('super_admin')->user()->name,
            [
                'subject_type' => 'super_admin_invite',
                'subject_id'   => $invite->id,
                'description'  => "Invited {$validated['email']} as {$validated['role']}",
            ]
        );

        return back()->with('success', $mailNote);
    }

    public function showAccept(string $token)
    {
        $invite = SuperAdminInvite::query()->where('token', $token)->first();

        if (! $invite || ! $invite->isUsable()) {
            abort(404, 'Invite link is invalid or has expired.');
        }

        return view('central.pages.auth.accept-invite', [
            'token'  => $token,
            'invite' => [
                'name'  => $invite->name,
                'email' => $invite->email,
                'role'  => $invite->role,
            ],
        ]);
    }

    public function acceptInvite(Request $request, string $token)
    {
        $request->validate([
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
            ],
        ], [
            'password.regex' => 'Password must contain uppercase, lowercase, number and special character.',
        ]);

        try {
            $admin = DB::connection('central')->transaction(function () use ($request, $token) {
                $invite = SuperAdminInvite::query()
                    ->where('token', $token)
                    ->lockForUpdate()
                    ->first();

                if (! $invite || ! $invite->isUsable()) {
                    abort(404, 'Invite link is invalid or has expired.');
                }

                if (SuperAdmin::query()->where('email', $invite->email)->exists()) {
                    $invite->markAccepted();

                    return null;
                }

                $admin = SuperAdmin::query()->create([
                    'name'     => $invite->name,
                    'email'    => $invite->email,
                    'password' => Hash::make($request->password),
                    'role'     => $invite->role,
                    'status'   => 'active',
                ]);

                $invite->markAccepted();

                return $admin;
            });
        } catch (HttpException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Super admin invite accept failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to create your account. Please try again or request a new invite.');
        }

        if (! $admin) {
            return redirect()
                ->route('super-admin.login.get')
                ->with('error', 'An account with this email already exists. Please sign in.');
        }

        AuditLog::record(
            'super_admin.registered',
            null,
            'super_admin',
            $admin->id,
            $admin->name,
            [
                'description' => "New {$admin->role} registered via invite",
            ]
        );

        Auth::guard('super_admin')->login($admin);

        return redirect()
            ->route('super-admin.index.get')
            ->with('success', 'Welcome to Ledrix! Your account is ready.');
    }

    public function revoke(int $id)
    {
        abort_unless(Auth::guard('super_admin')->user()?->isOwner(), 403);

        $invite = SuperAdminInvite::query()
            ->whereNull('accepted_at')
            ->findOrFail($id);

        $email = $invite->email;
        $invite->delete();

        AuditLog::record(
            'super_admin.invite_revoked',
            null,
            'super_admin',
            Auth::guard('super_admin')->id(),
            Auth::guard('super_admin')->user()->name,
            [
                'subject_type' => 'super_admin_invite',
                'subject_id'   => $id,
                'description'  => "Revoked invite for {$email}",
            ]
        );

        return back()->with('success', 'Invite revoked.');
    }
}
