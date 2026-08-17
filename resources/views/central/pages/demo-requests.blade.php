@extends('central.layout.layout')

@section('title', 'Ledrix | Demo Requests')

@section('central-content')
    <div class="sa-page-header">
        <div>
            <h1>Demo Requests</h1>
            <p>Inbound demos from <code>/lp/demo</code> and the marketing contact form</p>
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
            <form method="GET" action="{{ route('super-admin.demo-requests.get') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All</option>
                        @foreach (['pending', 'contacted', 'demo_sent', 'active', 'inactive'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sa-primary">Filter</button>
                    <a href="{{ route('super-admin.demo-requests.get') }}" class="btn btn-outline-secondary">Reset</a>
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
                            <th>Contact</th>
                            <th>Company</th>
                            <th>Source</th>
                            <th>Notes</th>
                            <th>Tenant</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($demos as $demo)
                            <tr>
                                <td data-label="#">{{ $demo->id }}</td>
                                <td data-label="Contact">
                                    <strong>{{ $demo->name }}</strong><br>
                                    <small><a href="mailto:{{ $demo->email }}">{{ $demo->email }}</a></small>
                                </td>
                                <td data-label="Company">{{ $demo->company ?? '—' }}</td>
                                <td data-label="Source">
                                    @php
                                        $mkt = \App\Support\MarketingAttribution::fromEmbeddedNotes($demo->description);
                                        $sourceBadge = $mkt['source'] ?? $demo->marketingSource();
                                    @endphp
                                    @if ($sourceBadge)
                                        <span class="badge bg-primary">{{ str_replace('_', ' ', $sourceBadge) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                    @if ($mkt['landing'] ?? $demo->marketingLanding())
                                        <br><small class="text-muted">{{ $mkt['landing'] ?? $demo->marketingLanding() }}</small>
                                    @endif
                                    @if (($mkt['pairs']['fbclid'] ?? null) || ($mkt['pairs']['gclid'] ?? null) || ($mkt['pairs']['ttclid'] ?? null))
                                        <br><small class="text-muted">
                                            @if (! empty($mkt['pairs']['fbclid'])) FB {{ \Illuminate\Support\Str::limit($mkt['pairs']['fbclid'], 12) }} @endif
                                            @if (! empty($mkt['pairs']['gclid'])) · G {{ \Illuminate\Support\Str::limit($mkt['pairs']['gclid'], 12) }} @endif
                                            @if (! empty($mkt['pairs']['ttclid'])) · TT {{ \Illuminate\Support\Str::limit($mkt['pairs']['ttclid'], 12) }} @endif
                                        </small>
                                    @endif
                                </td>
                                <td data-label="Notes">
                                    <small class="text-muted">{{ \Illuminate\Support\Str::limit($demo->notesWithoutMarketing(), 80) ?: '—' }}</small>
                                </td>
                                <td data-label="Tenant">
                                    @if ($demo->tenant)
                                        <a href="{{ route('super-admin.tenant.show', $demo->tenant->id) }}">{{ $demo->tenant->name }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Status">
                                    @php
                                        $statusClass = match ($demo->status) {
                                            'pending' => 'warning',
                                            'contacted' => 'info',
                                            'demo_sent' => 'primary',
                                            'active' => 'success',
                                            'inactive' => 'secondary',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $demo->status)) }}</span>
                                    @if ($demo->demo_expires_at)
                                        <br><small class="text-muted">Expires {{ $demo->demo_expires_at->format('M d, Y') }}</small>
                                    @endif
                                </td>
                                <td data-label="Submitted">
                                    {{ $demo->created_at?->format('d M, Y') }}<br>
                                    <small class="text-muted">{{ $demo->created_at?->diffForHumans() }}</small>
                                </td>
                                <td data-label="Action">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#editDemo{{ $demo->id }}">Update</button>
                                </td>
                            </tr>

                            <div class="modal fade" id="editDemo{{ $demo->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="{{ route('super-admin.demo-requests.update', $demo->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-header">
                                                <h5 class="modal-title">Update demo #{{ $demo->id }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select name="status" class="form-select" required>
                                                        @foreach (['pending', 'contacted', 'demo_sent', 'active', 'inactive'] as $status)
                                                            <option value="{{ $status }}" @selected($demo->status === $status)>
                                                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Linked tenant</label>
                                                    <select name="tenant_id" class="form-select">
                                                        <option value="">None</option>
                                                        @foreach ($tenants as $tenant)
                                                            <option value="{{ $tenant->id }}" @selected((int) $demo->tenant_id === (int) $tenant->id)>
                                                                {{ $tenant->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Expires at</label>
                                                    <input type="date" name="demo_expires_at" class="form-control"
                                                        value="{{ $demo->demo_expires_at?->format('Y-m-d') }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Marketing attribution</label>
                                                    @include('central.partials.attribution-block', [
                                                        'source' => $mkt['source'] ?? $demo->marketingSource(),
                                                        'landingPath' => $mkt['landing'] ?? $demo->marketingLanding(),
                                                        'attribution' => $mkt['pairs'] ?? [],
                                                    ])
                                                </div>
                                                <div class="mb-0">
                                                    <label class="form-label">Admin note</label>
                                                    <textarea name="admin_note" class="form-control" rows="3"
                                                        placeholder="Optional note appended to description"></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-sa-primary">Save</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No demo requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($demos->hasPages())
                <div class="sa-pagination">{{ $demos->links() }}</div>
            @endif
        </div>
    </div>
@endsection
