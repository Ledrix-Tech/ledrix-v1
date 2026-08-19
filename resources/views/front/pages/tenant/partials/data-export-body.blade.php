@php
    $isAdminOrg = ($organizationPortal ?? 'tenant') === 'admin';
@endphp
<div class="container py-4" style="max-width: 760px;">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">Workspace data export</h4>
            <p class="text-muted mb-0 small">Request a ZIP of your CRM and billing rows (CSV). This is not a SQL dump of the platform database. Extra fees may apply — Super Admin will confirm before preparing the file.</p>
        </div>
        <a href="{{ org_route('overview') }}" class="btn btn-outline-secondary btn-sm">Overview</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($current)
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <p class="mb-2"><strong>Latest request:</strong> #{{ $current->id }}</p>
                <p class="mb-2"><strong>Status:</strong>
                    <span class="badge bg-{{ $current->status === 'ready' ? 'success' : ($current->status === 'rejected' ? 'danger' : 'secondary') }}">
                        {{ str_replace('_', ' ', $current->status) }}
                    </span>
                </p>
                @if ($current->reason)
                    <p class="mb-2 small text-muted">Reason: {{ $current->reason }}</p>
                @endif
                @if ($current->status === 'rejected' && $current->rejection_note)
                    <div class="alert alert-warning mb-2">{{ $current->rejection_note }}</div>
                @endif
                @if ($current->tenantCanDownload())
                    <a href="{{ URL::temporarySignedRoute(
                        $isAdminOrg ? 'admin.org.data-export.download' : 'tenant.data-export.download',
                        $current->expires_at,
                        ['export' => $current->id]
                    ) }}" class="btn btn-primary btn-sm">Download ZIP</a>
                    <p class="small text-muted mt-2 mb-0">Link expires {{ $current->expires_at?->format('M d, Y H:i') }}.</p>
                @elseif ($current->isReady() && $current->tenantLinkExpired())
                    <p class="small text-muted mb-0">The tenant download window has expired. Submit a new request if you need the files again.</p>
                @elseif (in_array($current->status, ['pending', 'approved', 'processing'], true))
                    <p class="small text-muted mb-0">Waiting on Super Admin review or file generation.</p>
                @endif
            </div>
        </div>
    @endif

    @if (! $busy)
        <form method="POST" action="{{ org_route('data-export.store') }}" class="card shadow-sm border-0">
            @csrf
            <div class="card-body">
                <label class="form-label" for="reason">Why do you need this export?</label>
                <textarea id="reason" name="reason" rows="4" required maxlength="1000"
                    class="form-control @error('reason') is-invalid @enderror">{{ old('reason') }}</textarea>
                @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <p class="small text-muted mt-2 mb-3">Passwords, 2FA secrets, and payment-gateway keys are omitted from the package.</p>
                <button type="submit" class="btn btn-primary">Request export</button>
            </div>
        </form>
    @endif
</div>
