<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\Central\AuditLog;
use App\Models\Central\SuperAdmin;
use App\Models\Central\SuperAdminInvite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    public function index()
    {
        $actor = Auth::guard('super_admin')->user();

        $members = SuperAdmin::query()
            ->orderByRaw("CASE role WHEN 'owner' THEN 1 WHEN 'admin' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->get();

        $pendingInvites = SuperAdminInvite::query()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get();

        return view('central.pages.team', [
            'members'        => $members,
            'pendingInvites' => $pendingInvites,
            'isOwner'        => $actor?->isOwner() ?? false,
            'twoFactorOn'    => (bool) $actor?->two_factor_secret,
        ]);
    }

    public function updateStatus(Request $request, int $id)
    {
        $actor = Auth::guard('super_admin')->user();
        abort_unless($actor?->isOwner(), 403, 'Only the owner can change team member status.');

        $member = SuperAdmin::query()->findOrFail($id);

        abort_if($member->id === $actor->id, 422, 'You cannot change your own status.');
        abort_if($member->isOwner(), 422, 'Owner status cannot be changed here.');

        $validated = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $member->update(['status' => $validated['status']]);

        AuditLog::record(
            'super_admin.status_changed',
            null,
            'super_admin',
            $actor->id,
            $actor->name,
            [
                'subject_type' => 'super_admin',
                'subject_id'   => $member->id,
                'description'  => "Set {$member->email} to {$validated['status']}",
                'after'        => ['status' => $validated['status']],
            ]
        );

        return back()->with('success', 'Team member status updated.');
    }

    public function updateRole(Request $request, int $id)
    {
        $actor = Auth::guard('super_admin')->user();
        abort_unless($actor?->isOwner(), 403, 'Only the owner can change roles.');

        $member = SuperAdmin::query()->findOrFail($id);

        abort_if($member->id === $actor->id, 422, 'You cannot change your own role.');
        abort_if($member->isOwner(), 422, 'Owner role cannot be changed here.');

        $validated = $request->validate([
            'role' => ['required', Rule::in(['admin', 'support'])],
        ]);

        $before = $member->role;
        $member->update(['role' => $validated['role']]);

        AuditLog::record(
            'super_admin.role_changed',
            null,
            'super_admin',
            $actor->id,
            $actor->name,
            [
                'subject_type' => 'super_admin',
                'subject_id'   => $member->id,
                'description'  => "Role {$before} → {$validated['role']} for {$member->email}",
                'before'       => ['role' => $before],
                'after'        => ['role' => $validated['role']],
            ]
        );

        return back()->with('success', 'Team member role updated.');
    }
}
