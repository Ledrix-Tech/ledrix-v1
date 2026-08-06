@extends('central.layout.layout')

@section('title', 'Ledrix | Webhook Events')

@section('central-content')
    <div class="sa-page-header">
        <div>
            <h1>Webhook Events</h1>
            <p>Read-only log of platform billing / provider webhook deliveries</p>
        </div>
    </div>

    <div class="sa-card mb-3">
        <div class="sa-card-body">
            <form method="GET" action="{{ route('super-admin.webhook-events.get') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label" for="provider">Provider</label>
                    <select name="provider" id="provider" class="form-select">
                        <option value="">All</option>
                        @foreach (['stripe', 'payfast', 'jazzcash', 'meezan', 'payoneer', 'paypal'] as $provider)
                            <option value="{{ $provider }}" @selected(request('provider') === $provider)>{{ ucfirst($provider) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All</option>
                        @foreach (['pending', 'processed', 'failed', 'ignored'] as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sa-primary">Filter</button>
                    <a href="{{ route('super-admin.webhook-events.get') }}" class="btn btn-outline-secondary">Reset</a>
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
                            <th>Provider</th>
                            <th>Event</th>
                            <th>Tenant</th>
                            <th>Status</th>
                            <th>Attempts</th>
                            <th>Received</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($events as $event)
                            <tr>
                                <td data-label="#">{{ $event->id }}</td>
                                <td data-label="Provider"><span class="badge bg-light text-dark border">{{ $event->provider }}</span></td>
                                <td data-label="Event">
                                    <strong>{{ $event->event_type }}</strong><br>
                                    <small class="text-muted"><code>{{ \Illuminate\Support\Str::limit($event->event_id, 28) }}</code></small>
                                    @if ($event->error_message)
                                        <br><small class="text-danger">{{ \Illuminate\Support\Str::limit($event->error_message, 60) }}</small>
                                    @endif
                                </td>
                                <td data-label="Tenant">
                                    @if ($event->tenant)
                                        <a href="{{ route('super-admin.tenant.show', $event->tenant->id) }}">{{ $event->tenant->name }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Status">
                                    @php
                                        $statusClass = match ($event->status) {
                                            'processed' => 'success',
                                            'pending' => 'warning',
                                            'failed' => 'danger',
                                            'ignored' => 'secondary',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ ucfirst($event->status) }}</span>
                                </td>
                                <td data-label="Attempts">{{ $event->attempts ?? 0 }}</td>
                                <td data-label="Received">
                                    {{ $event->created_at?->format('M d, Y H:i') }}
                                    @if ($event->processed_at)
                                        <br><small class="text-muted">Processed {{ $event->processed_at->diffForHumans() }}</small>
                                    @endif
                                </td>
                                <td data-label="">
                                    <a href="{{ route('super-admin.webhook-events.show', $event->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No webhook events recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($events->hasPages())
                <div class="sa-pagination">{{ $events->links() }}</div>
            @endif
        </div>
    </div>
@endsection
