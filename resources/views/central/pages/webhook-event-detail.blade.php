@extends('central.layout.layout')

@section('title', 'Ledrix | Webhook Event')

@section('central-content')
    <div class="sa-page-header">
        <div>
            <a href="{{ route('super-admin.webhook-events.get') }}" class="text-muted small text-decoration-none">&larr; All events</a>
            <h1 class="mt-1">Event #{{ $event->id }}</h1>
            <p>{{ $event->provider }} · {{ $event->event_type }}</p>
        </div>
        @if ($event->canRetry())
            <form method="POST" action="{{ route('super-admin.webhook-events.retry', $event->id) }}">
                @csrf
                <button type="submit" class="btn btn-outline-warning btn-sm">Mark pending (retry)</button>
            </form>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="sa-card mb-3">
        <div class="sa-card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>Status</strong><br>{{ ucfirst($event->status) }}</div>
                <div class="col-md-4"><strong>Event ID</strong><br><code>{{ $event->event_id }}</code></div>
                <div class="col-md-4">
                    <strong>Tenant</strong><br>
                    @if ($event->tenant)
                        <a href="{{ route('super-admin.tenant.show', $event->tenant->id) }}">{{ $event->tenant->name }}</a>
                    @else
                        —
                    @endif
                </div>
                <div class="col-md-4"><strong>Attempts</strong><br>{{ $event->attempts }}</div>
                <div class="col-md-4"><strong>Received</strong><br>{{ $event->created_at?->format('M d, Y H:i') }}</div>
                <div class="col-md-4"><strong>Processed</strong><br>{{ $event->processed_at?->format('M d, Y H:i') ?? '—' }}</div>
            </div>
            @if ($event->error_message)
                <div class="alert alert-danger mt-3 mb-0">{{ $event->error_message }}</div>
            @endif
        </div>
    </div>

    <div class="sa-card">
        <div class="sa-card-header"><h4>Payload</h4></div>
        <div class="sa-card-body">
            <pre class="mb-0 small" style="white-space: pre-wrap; max-height: 480px; overflow: auto;">{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
@endsection
