@extends('sellers.layout.layout')

@section('title', 'Seller | Briefs Hub')

@section('sellers-content')
    @php
        use App\Support\BriefServiceCatalog;
    @endphp

    <div class="crm-page-header">
        <div>
            <h1>Project Briefs</h1>
            <p>
                Track client briefs per order. Clients submit project info in their portal — you review and update status here.
            </p>
        </div>
        <div class="crm-page-actions">
            <button type="button" class="btn btn-sm btn-crm-outline js-copy-link" data-url="{{ $clientPortalUrl }}">
                <i class="bi bi-link-45deg me-1"></i> Copy client portal link
            </button>
        </div>
    </div>

    <div class="crm-dash-kpis mb-4">
        <div class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Total briefs</div>
                    <div class="crm-dash-kpi-value">{{ $stats['total'] }}</div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--blue">
                    <i class="bi bi-journal-text"></i>
                </div>
            </div>
        </div>
        <div class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Pending</div>
                    <div class="crm-dash-kpi-value">{{ $stats['pending'] }}</div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--teal">
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>
        <div class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">In progress</div>
                    <div class="crm-dash-kpi-value">{{ $stats['in_progress'] }}</div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--green">
                    <i class="bi bi-pencil-square"></i>
                </div>
            </div>
        </div>
        <div class="crm-dash-kpi">
            <div class="crm-dash-kpi-top">
                <div>
                    <div class="crm-dash-kpi-label">Completed</div>
                    <div class="crm-dash-kpi-value">{{ $stats['completed'] }}</div>
                </div>
                <div class="crm-dash-kpi-icon crm-dash-kpi-icon--green">
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Order</th>
                            <th>Brand</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            @php
                                $order = $row['order'];
                                $status = $row['status'];
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $order->client?->name ?? '—' }}</strong><br>
                                    <small class="text-muted">{{ $order->client?->email }}</small>
                                </td>
                                <td>{{ $order->service_name }}</td>
                                <td>INV#000{{ $order->id }}</td>
                                <td>{{ $order->brand?->brand_name ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ BriefServiceCatalog::briefStatusBadgeClass($status) }}">
                                        {{ BriefServiceCatalog::briefStatusLabel($status) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex flex-wrap gap-1 justify-content-end">
                                        <a href="{{ route('seller.client-briefs.get', ['id' => $order->client_id, 'order' => $order->id]) }}"
                                            class="btn btn-sm btn-crm-primary">
                                            View brief
                                        </a>
                                        <button type="button" class="btn btn-sm btn-crm-outline js-copy-link"
                                            data-url="{{ $row['public_url'] }}">
                                            Copy brief link
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    No briefs yet. Briefs appear here when clients have qualifying service orders.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.querySelectorAll('.js-copy-link').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = btn.getAttribute('data-url');
                if (!url || !navigator.clipboard) {
                    return;
                }

                navigator.clipboard.writeText(url).then(function () {
                    var original = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check2 me-1"></i> Copied';
                    setTimeout(function () {
                        btn.innerHTML = original;
                    }, 2000);
                });
            });
        });
    </script>
@endsection
