<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">Plan &amp; features</h4>
            <p class="text-muted mb-0 small">
                {{ $plan?->name ?? 'No plan' }}
                · read-only view of what’s included
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ org_route('overview') }}" class="btn btn-outline-secondary btn-sm">Overview</a>
            <a href="{{ org_route('billing') }}" class="btn btn-outline-primary btn-sm">Billing</a>
            <a href="{{ route('pricing.get') }}" class="btn btn-primary btn-sm" target="_blank">Compare plans</a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        @foreach ($limits as $row)
            @php
                $max = $row['max'];
                $unlimited = $max === null || (int) $max === -1;
            @endphp
            <div class="col-6 col-md-4 col-lg-2">
                <div class="border rounded p-3 text-center h-100">
                    <div class="fw-bold fs-5">
                        {{ (int) $row['used'] }}@if (! $unlimited)<span class="fs-6 text-muted fw-normal">/{{ (int) $max }}</span>@endif
                    </div>
                    <div class="small text-muted">{{ $row['label'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header">Feature matrix</div>
        <div class="card-body p-0">
            @forelse ($featureMatrix as $group => $rows)
                <div class="px-3 pt-3"><strong class="text-uppercase small text-muted">{{ $group }}</strong></div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Feature</th>
                                <th class="text-center">Included</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $feature)
                                <tr>
                                    <td>
                                        {{ $feature['label'] ?? ($feature['key'] ?? 'Feature') }}
                                        @if (! empty($feature['description']))
                                            <br><small class="text-muted">{{ $feature['description'] }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($feature['effective'] ?? false)
                                            <span class="badge bg-success">On</span>
                                        @else
                                            <span class="badge bg-secondary">Off</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @empty
                <p class="text-muted p-3 mb-0">No feature definitions available.</p>
            @endforelse
        </div>
    </div>

    <div class="alert alert-info mt-4 mb-0">
        Need a higher plan?
        <a href="{{ route('pricing.get') }}" class="alert-link">View pricing</a>
        or
        <a href="{{ org_route('support.create') }}" class="alert-link">contact support</a>
        to upgrade.
    </div>
</div>
