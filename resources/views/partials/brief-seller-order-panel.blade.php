@php
    use App\Support\BriefServiceCatalog;

    $brief = $order->brief;
    $displayStatus = BriefServiceCatalog::briefStatus($brief);
    $storedStatus = $brief?->status ?? 'pending';
    $publicUrl = $brief?->brief_token ? route('brief.show', ['token' => $brief->brief_token]) : null;
@endphp

<div class="brief-seller-panel">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <span class="badge {{ BriefServiceCatalog::briefStatusBadgeClass($displayStatus) }}">
                {{ BriefServiceCatalog::briefStatusLabel($displayStatus) }}
            </span>
            <span class="text-muted ms-2 small">{{ $order->service_name }} · INV#000{{ $order->id }}</span>
        </div>
        @if ($publicUrl)
            <button type="button" class="btn btn-sm btn-crm-outline js-copy-link" data-url="{{ $publicUrl }}">
                <i class="bi bi-link-45deg me-1"></i> Copy client brief link
            </button>
        @endif
    </div>

    <form method="POST" action="{{ route('seller.brief-status.post') }}" class="crm-card mb-4">
        @csrf
        <input type="hidden" name="order_id" value="{{ $order->id }}">
        <div class="crm-card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label fw-semibold mb-1">Update status</label>
                    <select name="status" class="form-select" required>
                        <option value="pending" @selected($storedStatus === 'pending')>Pending</option>
                        <option value="progress" @selected($storedStatus === 'progress')>In progress</option>
                        <option value="completed" @selected($storedStatus === 'completed')>Completed</option>
                    </select>
                    <div class="crm-field-hint">Clients submit project info; you track review progress here.</div>
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-crm-primary">
                        <i class="bi bi-check2-circle me-1"></i> Save status
                    </button>
                </div>
            </div>
        </div>
    </form>

    <div class="crm-card">
        <div class="crm-card-body">
            <h5 class="mb-3">Submitted project info</h5>
            @include('partials.brief-readonly-display', [
                'briefMeta' => $brief?->meta ?? [],
            ])
        </div>
    </div>
</div>
