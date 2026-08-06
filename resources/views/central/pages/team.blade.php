@extends('central.layout.layout')

@section('title', 'Ledrix | Team')

@section('central-content')
    <div class="sa-page-header">
        <div>
            <h1>Super Admin Team</h1>
            <p>Platform operators with owner, admin, or support roles</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if ($twoFactorOn ?? false)
                <form method="POST" action="{{ route('super-admin.2fa.disable') }}" class="d-inline"
                    onsubmit="var c=prompt('Enter authenticator or recovery code to disable 2FA'); if(!c) return false; this.code.value=c;">
                    @csrf
                    <input type="hidden" name="code" value="">
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Disable 2FA</button>
                </form>
            @else
                <a href="{{ route('super-admin.2fa.setup') }}" class="btn btn-outline-primary btn-sm">Enable 2FA</a>
            @endif
            @if ($isOwner)
                <button type="button" class="btn btn-sa-primary" data-bs-toggle="modal" data-bs-target="#inviteAdmin">
                    <i class="bi bi-person-plus me-1"></i> Invite member
                </button>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="sa-card">
        <div class="sa-card-body p-0">
            <div class="sa-table-wrap">
                <table class="table sa-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last seen</th>
                            @if ($isOwner)
                                <th>Action</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($members as $member)
                            <tr>
                                <td data-label="Name"><strong>{{ $member->name }}</strong></td>
                                <td data-label="Email">{{ $member->email }}</td>
                                <td data-label="Role">
                                    <span class="badge bg-{{ $member->role === 'owner' ? 'dark' : ($member->role === 'admin' ? 'primary' : 'info') }}">
                                        {{ ucfirst($member->role) }}
                                    </span>
                                </td>
                                <td data-label="Status">
                                    <span class="badge bg-{{ $member->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($member->status) }}
                                    </span>
                                </td>
                                <td data-label="Last seen">
                                    {{ $member->last_seen?->diffForHumans() ?? '—' }}
                                    @if ($member->last_login_ip)
                                        <br><small class="text-muted">{{ $member->last_login_ip }}</small>
                                    @endif
                                </td>
                                @if ($isOwner)
                                    <td data-label="Action">
                                        @if (! $member->isOwner() && $member->id !== auth('super_admin')->id())
                                            <div class="d-flex flex-column gap-1">
                                                <form method="POST" action="{{ route('super-admin.team.status', $member->id) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                        <option value="active" @selected($member->status === 'active')>Active</option>
                                                        <option value="inactive" @selected($member->status === 'inactive')>Inactive</option>
                                                    </select>
                                                </form>
                                                <form method="POST" action="{{ route('super-admin.team.role', $member->id) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                                                        <option value="admin" @selected($member->role === 'admin')>Admin</option>
                                                        <option value="support" @selected($member->role === 'support')>Support</option>
                                                    </select>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $isOwner ? 6 : 5 }}" class="text-center text-muted py-4">No team members found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($isOwner && ($pendingInvites ?? collect())->isNotEmpty())
        <div class="sa-card mt-3">
            <div class="sa-card-header"><h4>Pending invites</h4></div>
            <div class="sa-card-body p-0">
                <div class="sa-table-wrap">
                    <table class="table sa-table mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Expires</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pendingInvites as $invite)
                                <tr>
                                    <td>{{ $invite->name }}</td>
                                    <td>{{ $invite->email }}</td>
                                    <td>{{ ucfirst($invite->role) }}</td>
                                    <td>{{ $invite->expires_at?->format('M d, Y') }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('super-admin.invite.revoke', $invite->id) }}"
                                            onsubmit="return confirm('Revoke this invite?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Revoke</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    @if ($isOwner)
        <div class="modal fade" id="inviteAdmin" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('super-admin.invite.send') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Invite super admin</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label" for="invite-name">Name</label>
                                <input type="text" id="invite-name" name="name" class="form-control" required maxlength="255">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="invite-email">Email</label>
                                <input type="email" id="invite-email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="invite-role">Role</label>
                                <select id="invite-role" name="role" class="form-select" required>
                                    <option value="admin">Admin</option>
                                    <option value="support">Support</option>
                                </select>
                            </div>
                            <p class="text-muted small mt-3 mb-0">Invite link expires in 48 hours.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-sa-primary">Send invite</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
