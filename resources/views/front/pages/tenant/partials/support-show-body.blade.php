
<main class="py-5">
        <div class="container" style="max-width: 800px;">
            <a href="{{ org_route('support.index') }}" class="text-muted small text-decoration-none">&larr; All tickets</a>
            <h4 class="mt-1 mb-1">{{ $ticket->subject }}</h4>
            <p class="text-muted">
                #{{ $ticket->id }} · {{ ucfirst(str_replace('_', ' ', $ticket->category)) }} ·
                {{ ucfirst($ticket->priority) }} · {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
            </p>

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card shadow-sm mb-4">
                <div class="card-header">Original request</div>
                <div class="card-body" style="white-space: pre-wrap;">{{ $ticket->description }}</div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header">Conversation</div>
                <div class="card-body">
                    @forelse ($ticket->publicReplies as $reply)
                        <div class="border rounded p-3 mb-3">
                            <div class="d-flex justify-content-between mb-2">
                                <strong>{{ $reply->isFromSupport() ? 'Ledrix Support' : 'You' }}</strong>
                                <small class="text-muted">{{ $reply->created_at?->format('M d, Y H:i') }}</small>
                            </div>
                            <div style="white-space: pre-wrap;">{{ $reply->message }}</div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No replies yet. We’ll get back to you soon.</p>
                    @endforelse
                </div>
            </div>

            @if ($ticket->status !== 'closed')
                <div class="card shadow-sm">
                    <div class="card-header">Add a reply</div>
                    <div class="card-body">
                        <form method="POST" action="{{ org_route('support.reply', $ticket->id) }}">
                            @csrf
                            <textarea name="message" rows="4" class="form-control mb-3" required maxlength="5000"></textarea>
                            <button type="submit" class="btn btn-primary">Send reply</button>
                        </form>
                    </div>
                </div>
            @else
                <div class="alert alert-secondary">This ticket is closed.</div>
            @endif
        </div>
    </main>
