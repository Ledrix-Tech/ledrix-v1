<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">Workspace audit log</h4>
            <p class="text-muted mb-0 small">Read-only activity for {{ $tenant->name }}</p>
        </div>
        <a href="{{ org_route('overview') }}" class="btn btn-outline-secondary btn-sm">Overview</a>
    </div>

    <form method="GET" action="{{ org_route('audit-logs') }}" class="row g-2 mb-4">
        <div class="col-md-4">
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control" placeholder="Search description, actor, action">
        </div>
        <div class="col-md-3">
            <input type="text" name="action" value="{{ $filters['action'] ?? '' }}" class="form-control" placeholder="Action contains…">
        </div>
        <div class="col-md-3">
            <select name="actor_type" class="form-select">
                <option value="">All actors</option>
                @foreach ($actorTypes as $type)
                    <option value="{{ $type }}" @selected(($filters['actor_type'] ?? '') === $type)>{{ $type }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary w-100">Filter</button>
        </div>
    </form>

    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Actor</th>
                        <th>Action</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="small text-nowrap">{{ $log->created_at?->format('M d, Y H:i') }}</td>
                            <td class="small">
                                <span class="badge bg-secondary">{{ $log->actor_type }}</span>
                                {{ $log->actor_name ?? '—' }}
                            </td>
                            <td class="small font-monospace">{{ $log->action }}</td>
                            <td class="small">{{ $log->description ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-muted text-center py-4">No audit events yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="card-footer">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
