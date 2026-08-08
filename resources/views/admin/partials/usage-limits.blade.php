@php
    $usage = $saasUsage ?? null;
    $limits = $saasLimits ?? [];
    $plan = $saasPlan ?? null;
@endphp

@if ($usage)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h6 class="mb-0">Plan usage</h6>
                    <small class="text-muted">
                        {{ $plan?->name ?? 'Current plan' }}
                        @if (! empty($limits))
                            · limits from your subscription
                        @endif
                    </small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.org.plan') }}" class="btn btn-sm btn-outline-secondary">Features</a>
                    <a href="{{ route('pricing.get') }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">Upgrade</a>
                    <a href="{{ route('admin.org.billing') }}" class="btn btn-sm btn-outline-secondary">Billing</a>
                </div>
            </div>
            <div class="row g-2 text-center">
                @foreach ([
                    'Brands' => ['used' => $usage->total_brands, 'max' => $limits['brands'] ?? null],
                    'Sellers' => ['used' => $usage->total_sellers, 'max' => $limits['sellers'] ?? null],
                    'Admins' => ['used' => $usage->total_admins, 'max' => $limits['admins'] ?? null],
                    'Clients' => ['used' => $usage->total_clients, 'max' => $limits['clients'] ?? null],
                    'Orders' => ['used' => $usage->total_orders, 'max' => $limits['orders'] ?? null],
                    'Leads (mo)' => ['used' => $usage->leads_this_month, 'max' => $limits['leads_monthly'] ?? null],
                ] as $label => $row)
                    @php
                        $used = (int) ($row['used'] ?? 0);
                        $max = $row['max'];
                        $unlimited = $max === null || (int) $max === -1;
                        $atLimit = ! $unlimited && (int) $max > 0 && $used >= (int) $max;
                    @endphp
                    <div class="col-6 col-md-2">
                        <div class="border rounded p-2 h-100 {{ $atLimit ? 'border-warning bg-warning-subtle' : '' }}">
                            <div class="fw-bold fs-5">{{ $used }}@if (! $unlimited)<span class="text-muted fw-normal fs-6">/{{ (int) $max }}</span>@endif</div>
                            <div class="small text-muted">{{ $label }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
