@extends('central.layout.layout')

@section('title', 'Ledrix | Audit Logs')

@section('central-content')
    <div class="sa-page-header">
        <div>
            <h1>Audit Logs</h1>
            <p>
                Platform activity
                @if ($filterTenant)
                    for <strong>{{ $filterTenant->name }}</strong>
                @endif
                · <strong>{{ number_format($totalLogs ?? 0) }}</strong> total rows
                @if (($olderThan90 ?? 0) > 0)
                    · <strong>{{ number_format($olderThan90) }}</strong> older than 90 days
                @endif
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (auth('super_admin')->user()?->isAdmin())
        <div class="sa-card mb-4 border-warning">
            <div class="sa-card-header">
                <h4 class="mb-0">Clear audit logs</h4>
            </div>
            <div class="sa-card-body">
                <p class="text-muted small mb-3">
                    Clearing old logs keeps the central DB fast. Prefer deleting older rows; clearing everything is irreversible.
                </p>
                <div class="row g-3">
                    <div class="col-lg-6">
                        <form method="POST" action="{{ route('super-admin.audit-logs.clear') }}"
                            onsubmit="return confirm('Delete audit logs older than the selected period?')">
                            @csrf
                            <input type="hidden" name="mode" value="older_than">
                            <label class="form-label">Delete logs older than</label>
                            <div class="d-flex gap-2">
                                <select name="days" class="form-select" required>
                                    <option value="30">30 days</option>
                                    <option value="90" selected>90 days</option>
                                    <option value="180">180 days</option>
                                    <option value="365">365 days</option>
                                </select>
                                <button type="submit" class="btn btn-outline-danger text-nowrap">Clear older</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-6">
                        <form method="POST" action="{{ route('super-admin.audit-logs.clear') }}"
                            onsubmit="return confirm('This will permanently delete ALL audit logs. Continue?')">
                            @csrf
                            <input type="hidden" name="mode" value="all">
                            <label class="form-label">Clear everything (type <code>DELETE</code>)</label>
                            <div class="d-flex gap-2">
                                <input type="text" name="confirm_text" class="form-control" placeholder="DELETE" required autocomplete="off">
                                <button type="submit" class="btn btn-danger text-nowrap">Clear all</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="sa-card mb-4">
        <div class="sa-card-body">
            <form method="GET" action="{{ route('super-admin.audit-logs.get') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Tenant</label>
                    <select name="tenant_id" class="form-select">
                        <option value="">All tenants</option>
                        @foreach ($tenants as $tenant)
                            <option value="{{ $tenant->id }}" @selected((string) request('tenant_id') === (string) $tenant->id)>
                                {{ $tenant->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Actor type</label>
                    <select name="actor_type" class="form-select">
                        <option value="">All</option>
                        @foreach ($actorTypes as $type)
                            <option value="{{ $type }}" @selected(request('actor_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Action</label>
                    <input type="text" name="action" class="form-control" value="{{ request('action') }}"
                        placeholder="e.g. tenant.suspended">
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="from" class="form-control" value="{{ request('from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="to" class="form-control" value="{{ request('to') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" class="form-control" value="{{ request('q') }}"
                        placeholder="Description or actor">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sa-primary">Filter</button>
                    <a href="{{ route('super-admin.audit-logs.get') }}" class="btn btn-outline-secondary">Reset</a>
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
                            <th>When</th>
                            <th>Action</th>
                            <th>Tenant</th>
                            <th>Actor</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td data-label="When">
                                    {{ $log->created_at?->format('M d, Y H:i') ?? '—' }}
                                </td>
                                <td data-label="Action"><code>{{ $log->action }}</code></td>
                                <td data-label="Tenant">
                                    @if ($log->tenant)
                                        <a href="{{ route('super-admin.tenant.show', $log->tenant->id) }}">
                                            {{ $log->tenant->name }}
                                        </a>
                                    @elseif ($log->tenant_id)
                                        #{{ $log->tenant_id }}
                                    @else
                                        <span class="text-muted">Platform</span>
                                    @endif
                                </td>
                                <td data-label="Actor">
                                    <span class="badge bg-secondary">{{ $log->actor_type }}</span>
                                    {{ $log->actor_name ?? '—' }}
                                </td>
                                <td data-label="Description">
                                    {{ $log->description ?: '—' }}
                                    @if ($log->before || $log->after)
                                        <details class="mt-1">
                                            <summary class="small text-muted">Changes</summary>
                                            <pre class="small mb-0 mt-1" style="white-space: pre-wrap;">{{ json_encode(['before' => $log->before, 'after' => $log->after], JSON_PRETTY_PRINT) }}</pre>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No audit log entries found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($logs->hasPages())
                <div class="sa-pagination">{{ $logs->links() }}</div>
            @endif
        </div>
    </div>
@endsection
