@extends('central.layout.layout')

@section('title', 'Ledrix | Renewal Requests')

@section('central-content')
    <div class="sa-page-header">
        <div>
            <h1>Renewal Requests</h1>
            <p>Tokenized renewal approval emails sent to tenants. Cancel pending requests that should no longer be used.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="sa-card">
        <div class="sa-card-body p-0">
            <div class="sa-table-wrap">
                <table class="table sa-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tenant</th>
                            <th>Plan</th>
                            <th>Cycle</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Expires</th>
                            <th>Updated</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($renewals as $renewal)
                            @php
                                $statusClass = match ($renewal->status) {
                                    'pending' => $renewal->isPending() ? 'warning' : 'secondary',
                                    'approved' => 'success',
                                    'cancelled', 'expired' => 'secondary',
                                    default => 'secondary',
                                };
                                $statusLabel = $renewal->status === 'pending' && $renewal->isExpired()
                                    ? 'Expired'
                                    : ucfirst($renewal->status);
                            @endphp
                            <tr>
                                <td data-label="#">{{ $renewal->id }}</td>
                                <td data-label="Tenant">
                                    @if ($renewal->tenant)
                                        <a href="{{ route('super-admin.tenant.show', $renewal->tenant->id) }}">
                                            {{ $renewal->tenant->name }}
                                        </a><br>
                                        <small class="text-muted">{{ $renewal->requested_by_email ?: $renewal->tenant->email }}</small>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-label="Plan">{{ $renewal->plan?->name ?? '—' }}</td>
                                <td data-label="Cycle">{{ ucfirst($renewal->billing_cycle ?? '—') }}</td>
                                <td data-label="Amount">
                                    <strong>{{ number_format((float) $renewal->amount, 2) }}</strong>
                                </td>
                                <td data-label="Status">
                                    <span class="badge bg-{{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td data-label="Expires">{{ $renewal->expires_at?->format('M d, Y H:i') ?? '—' }}</td>
                                <td data-label="Updated">{{ $renewal->updated_at?->format('M d, Y H:i') ?? '—' }}</td>
                                <td data-label="Action">
                                    <div class="d-flex flex-wrap gap-1">
                                        @if ($renewal->tenant)
                                            <a href="{{ route('super-admin.tenant.show', $renewal->tenant->id) }}"
                                                class="btn btn-sm btn-outline-primary">Tenant</a>
                                        @endif
                                        @if ($renewal->isPending() && auth('super_admin')->user()?->isAdmin())
                                            <form method="POST"
                                                action="{{ route('super-admin.renewal-requests.cancel', $renewal->id) }}"
                                                class="d-inline"
                                                onsubmit="return confirm('Cancel this pending renewal request?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No renewal requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($renewals->hasPages())
                <div class="sa-pagination">{{ $renewals->links() }}</div>
            @endif
        </div>
    </div>
@endsection
