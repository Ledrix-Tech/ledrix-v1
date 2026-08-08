<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">API tokens</h4>
            <p class="text-muted mb-0 small">Authenticate Ledrix company/lead APIs for {{ $tenant->name }}</p>
        </div>
        <a href="{{ org_route('overview') }}" class="btn btn-outline-secondary btn-sm">Back to overview</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('new_api_token'))
        <div class="alert alert-warning">
            <strong>Copy this token now — it will not be shown again.</strong>
            <input type="text" class="form-control mt-2 font-monospace" readonly
                value="{{ session('new_api_token') }}" onclick="this.select()">
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header">Create token</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.org.api-tokens.store') }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label class="form-label" for="name">Name</label>
                    <input id="name" type="text" name="name" class="form-control" required maxlength="100"
                        placeholder="Production API" value="{{ old('name') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="abilities">Abilities</label>
                    <input id="abilities" type="text" name="abilities" class="form-control"
                        placeholder="* or leads:classify,company:check" value="{{ old('abilities') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="expires_at">Expires</label>
                    <input id="expires_at" type="date" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Create</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Last used</th>
                            <th>Expires</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($apiTokens as $token)
                            <tr>
                                <td>
                                    <strong>{{ $token->name }}</strong>
                                    @if (is_array($token->abilities) && count($token->abilities))
                                        <br><small class="text-muted">{{ implode(', ', $token->abilities) }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $token->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($token->status) }}
                                    </span>
                                </td>
                                <td>
                                    {{ $token->last_used_at?->diffForHumans() ?? 'Never' }}
                                    @if ($token->last_used_ip)
                                        <br><small class="text-muted">{{ $token->last_used_ip }}</small>
                                    @endif
                                </td>
                                <td>{{ $token->expires_at?->format('M d, Y') ?? '—' }}</td>
                                <td class="text-end">
                                    @if ($token->status === 'active')
                                        <form method="POST" action="{{ route('admin.org.api-tokens.revoke', $token->id) }}"
                                            onsubmit="return confirm('Revoke this API token?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Revoke</button>
                                        </form>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No API tokens yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
