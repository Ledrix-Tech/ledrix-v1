@extends('central.layout.layout')

@section('title', 'Ledrix | Ticket #' . $ticket->id)

@section('central-content')
    <div class="sa-page-header">
        <div>
            <a href="{{ route('super-admin.support-tickets.get') }}" class="sa-back">&larr; Back to tickets</a>
            <h1>{{ $ticket->subject }}</h1>
            <p>
                #{{ $ticket->id }} ·
                {{ ucfirst(str_replace('_', ' ', $ticket->category)) }} ·
                {{ ucfirst($ticket->priority) }} ·
                {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="sa-card mb-4">
                <div class="sa-card-header"><h4 class="mb-0">Description</h4></div>
                <div class="sa-card-body">
                    <p class="mb-0" style="white-space: pre-wrap;">{{ $ticket->description }}</p>
                </div>
            </div>

            <div class="sa-card mb-4">
                <div class="sa-card-header"><h4 class="mb-0">Conversation</h4></div>
                <div class="sa-card-body">
                    @forelse ($ticket->replies as $reply)
                        <div class="border rounded p-3 mb-3 {{ $reply->is_internal ? 'bg-light border-warning' : '' }}">
                            <div class="d-flex justify-content-between mb-2">
                                <strong>
                                    {{ $reply->sender_name }}
                                    <span class="badge bg-{{ $reply->isFromSupport() ? 'primary' : 'secondary' }}">
                                        {{ $reply->isFromSupport() ? 'Support' : 'Tenant' }}
                                    </span>
                                    @if ($reply->is_internal)
                                        <span class="badge bg-warning text-dark">Internal</span>
                                    @endif
                                </strong>
                                <small class="text-muted">{{ $reply->created_at?->format('M d, Y H:i') }}</small>
                            </div>
                            <div style="white-space: pre-wrap;">{{ $reply->message }}</div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No replies yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="sa-card">
                <div class="sa-card-header"><h4 class="mb-0">Reply</h4></div>
                <div class="sa-card-body">
                    <form method="POST" action="{{ route('super-admin.support-tickets.reply', $ticket->id) }}">
                        @csrf
                        <textarea name="message" rows="4" class="form-control mb-3" required placeholder="Write a reply..."></textarea>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_internal" value="1" id="internalNote">
                            <label class="form-check-label" for="internalNote">Internal note (hidden from tenant)</label>
                        </div>
                        <button type="submit" class="btn btn-sa-primary">Send</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="sa-card mb-4">
                <div class="sa-card-header"><h4 class="mb-0">Tenant</h4></div>
                <div class="sa-card-body">
                    @if ($ticket->tenant)
                        <p class="mb-1"><strong>{{ $ticket->tenant->name }}</strong></p>
                        <p class="mb-2"><a href="mailto:{{ $ticket->tenant->email }}">{{ $ticket->tenant->email }}</a></p>
                        <a href="{{ route('super-admin.tenant.show', $ticket->tenant->id) }}" class="btn btn-sm btn-outline-primary">Open tenant</a>
                    @endif
                </div>
            </div>

            <div class="sa-card">
                <div class="sa-card-header"><h4 class="mb-0">Actions</h4></div>
                <div class="sa-card-body d-flex flex-column gap-2">
                    <p class="small text-muted mb-1">Assigned: {{ $ticket->assignedTo?->name ?? 'Unassigned' }}</p>
                    @foreach ([
                        'assign' => 'Assign to me',
                        'hold' => 'Put on hold',
                        'resolve' => 'Mark resolved',
                        'close' => 'Close',
                        'reopen' => 'Reopen',
                    ] as $action => $label)
                        <form method="POST" action="{{ route('super-admin.support-tickets.status', $ticket->id) }}">
                            @csrf
                            <input type="hidden" name="action" value="{{ $action }}">
                            <button type="submit" class="btn btn-sm btn-outline-secondary w-100">{{ $label }}</button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
