@extends('central.layout.layout')

@section('title', 'Ledrix | Referrals')

@section('central-content')
    @php $canManage = auth('super_admin')->user()?->isAdmin() ?? false; @endphp
    <div class="sa-page-header">
        <div>
            <h1>Referrals</h1>
            <p>Issue referral codes and track conversions / rewards</p>
        </div>
        @if ($canManage)
            <button type="button" class="btn btn-sa-primary" data-bs-toggle="modal" data-bs-target="#issueReferral">
                <i class="bi bi-plus-lg me-1"></i> Issue code
            </button>
        @endif
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

    <div class="sa-card mb-3">
        <div class="sa-card-body">
            <form method="GET" action="{{ route('super-admin.referrals.get') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All</option>
                        @foreach (['pending', 'converted', 'rewarded', 'expired'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sa-primary">Filter</button>
                    <a href="{{ route('super-admin.referrals.get') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="sa-card">
        <div class="sa-card-body p-0">
            <div class="sa-table-wrap">
                <table class="table sa-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Code</th>
                            <th>Referrer</th>
                            <th>Referred</th>
                            <th>Reward</th>
                            <th>Status</th>
                            <th>Expires</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($referrals as $referral)
                            <tr>
                                <td data-label="#">{{ $referral->id }}</td>
                                <td data-label="Code"><code>{{ $referral->referral_code }}</code></td>
                                <td data-label="Referrer">
                                    @if ($referral->referrer)
                                        <a href="{{ route('super-admin.tenant.show', $referral->referrer->id) }}">{{ $referral->referrer->name }}</a>
                                        <br><small class="text-muted">{{ $referral->referrer->email }}</small>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-label="Referred">
                                    @if ($referral->referred)
                                        <a href="{{ route('super-admin.tenant.show', $referral->referred->id) }}">{{ $referral->referred->name }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Reward">
                                    {{ ucfirst($referral->reward_type) }}
                                    <br><small>{{ strtoupper($referral->currency) }} {{ number_format((float) $referral->reward_amount, 2) }}</small>
                                </td>
                                <td data-label="Status">
                                    @php
                                        $statusClass = match ($referral->status) {
                                            'pending' => 'warning',
                                            'converted' => 'info',
                                            'rewarded' => 'success',
                                            'expired' => 'secondary',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ ucfirst($referral->status) }}</span>
                                </td>
                                <td data-label="Expires">
                                    {{ $referral->expires_at?->format('M d, Y') ?? '—' }}
                                    @if ($referral->isExpired() && $referral->status === 'pending')
                                        <br><small class="text-danger">Past due</small>
                                    @endif
                                </td>
                                <td data-label="Action">
                                    @if ($canManage)
                                        @if (in_array($referral->status, ['pending', 'converted'], true))
                                            <form method="POST" action="{{ route('super-admin.referrals.reward', $referral->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success"
                                                    onclick="return confirm('Mark this referral as rewarded?')">Reward</button>
                                            </form>
                                        @endif
                                        @if ($referral->status !== 'rewarded' && $referral->status !== 'expired')
                                            <form method="POST" action="{{ route('super-admin.referrals.expire', $referral->id) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary"
                                                    onclick="return confirm('Expire this referral code?')">Expire</button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No referrals yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($referrals->hasPages())
                <div class="sa-pagination">{{ $referrals->links() }}</div>
            @endif
        </div>
    </div>

    @if ($canManage)
        <div class="modal fade" id="issueReferral" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="{{ route('super-admin.referrals.issue') }}">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Issue referral code</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Referrer tenant</label>
                                <select name="referrer_tenant_id" class="form-select" required>
                                    <option value="">Select tenant</option>
                                    @foreach ($tenants as $tenant)
                                        <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Reward type</label>
                                <select name="reward_type" class="form-select" required>
                                    <option value="credit">Account credit</option>
                                    <option value="discount">Discount</option>
                                    <option value="cash">Cash</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-7 mb-3">
                                    <label class="form-label">Reward amount</label>
                                    <input type="number" name="reward_amount" class="form-control" min="0" step="0.01" value="50" required>
                                </div>
                                <div class="col-md-5 mb-3">
                                    <label class="form-label">Currency</label>
                                    <select name="currency" class="form-select" required>
                                        <option value="USD">USD</option>
                                        <option value="PKR">PKR</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Expires at</label>
                                <input type="date" name="expires_at" class="form-control"
                                    value="{{ now()->addMonths(6)->format('Y-m-d') }}">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-sa-primary">Issue code</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endsection
