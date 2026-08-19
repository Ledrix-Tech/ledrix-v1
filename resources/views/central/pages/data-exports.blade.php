@extends('central.layout.layout')

@section('title', 'Ledrix | Data Exports')

@section('central-content')
    @php $canManage = auth('super_admin')->user()?->isAdmin() ?? false; @endphp

    <div class="sa-page-header">
        <div>
            <h1>Workspace data exports</h1>
            <p>Approve tenant requests or download tenant-scoped CSV packages (not a full DB dump)</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="sa-card mb-3">
        <div class="sa-card-body">
            <form method="GET" action="{{ route('super-admin.data-exports.get') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All</option>
                        @foreach (['pending', 'approved', 'processing', 'ready', 'rejected', 'failed', 'expired'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sa-primary">Filter</button>
                    <a href="{{ route('super-admin.data-exports.get') }}" class="btn btn-outline-secondary">Reset</a>
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
                            <th>Tenant</th>
                            <th>Requested by</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Ready</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($exports as $export)
                            <tr>
                                <td data-label="#">{{ $export->id }}</td>
                                <td data-label="Tenant">
                                    @if ($export->tenant)
                                        <a href="{{ route('super-admin.tenant.show', $export->tenant_id) }}">{{ $export->tenant->name }}</a>
                                        <br><small class="text-muted">{{ $export->tenant->email }}</small>
                                    @else
                                        #{{ $export->tenant_id }}
                                    @endif
                                </td>
                                <td data-label="Requested by">
                                    {{ $export->requested_by_name ?? '—' }}
                                    <br><small class="text-muted">{{ $export->requested_by_type }}</small>
                                </td>
                                <td data-label="Reason"><small>{{ \Illuminate\Support\Str::limit($export->reason, 80) }}</small></td>
                                <td data-label="Status">
                                    <span class="badge bg-{{ $export->status === 'ready' ? 'success' : ($export->status === 'pending' ? 'warning' : 'secondary') }}">
                                        {{ $export->status }}
                                    </span>
                                    @if ($export->rejection_note)
                                        <br><small class="text-danger">{{ $export->rejection_note }}</small>
                                    @endif
                                </td>
                                <td data-label="Ready">{{ $export->ready_at?->format('M d, Y H:i') ?? '—' }}</td>
                                <td data-label="Action">
                                    @if ($export->isReady())
                                        <a class="btn btn-sm btn-sa-primary" href="{{ route('super-admin.data-exports.download', $export->id) }}">Download</a>
                                    @endif
                                    @if ($canManage && $export->isPending())
                                        <form method="POST" action="{{ route('super-admin.data-exports.approve', $export->id) }}" class="d-inline">
                                            @csrf
                                            <button class="btn btn-sm btn-success" type="submit">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('super-admin.data-exports.reject', $export->id) }}" class="d-inline mt-1"
                                            onsubmit="return confirm('Reject this export request?');">
                                            @csrf
                                            <input type="text" name="rejection_note" class="form-control form-control-sm d-inline-block" style="width: 160px;" placeholder="Reason" required>
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Reject</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No export requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($exports->hasPages())
            <div class="sa-card-body">{{ $exports->links() }}</div>
        @endif
    </div>
@endsection
