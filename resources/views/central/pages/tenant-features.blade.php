@extends('central.layout.layout')

@section('title', 'Ledrix | Tenant Plan & Access')

@section('central-content')
    @php $canManage = auth('super_admin')->user()?->isAdmin() ?? false; @endphp

    <div class="sa-page-header">
        <div>
            <a href="{{ route('super-admin.tenant.show', $tenant->id) }}" class="sa-back">&larr; Back to tenant</a>
            <h1>Plan &amp; access — {{ $tenant->name }}</h1>
            <p>
                Subscribed package: <strong>{{ $tenant->plan?->name ?? 'None assigned' }}</strong>
                @if ($tenant->plan && $canManage)
                    · <a href="{{ route('super-admin.pricing-packages.get') }}">Edit pricing packages</a>
                @endif
            </p>
        </div>
        @if ($hasOverrides && $canManage)
            <form method="POST" action="{{ route('super-admin.tenant.features.reset', $tenant->id) }}"
                onsubmit="return confirm('Clear all feature and limit overrides? Package defaults will apply immediately.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset to package defaults
                </button>
            </form>
        @endif
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @php
        $activeFeatureOverride = $tenant->featureOverride && ! $tenant->featureOverride->isExpired();
        $activeLimitOverride = $tenant->limitOverride && ! $tenant->limitOverride->isExpired();
    @endphp

    @if ($activeFeatureOverride || $activeLimitOverride)
        <div class="alert alert-info">
            <strong>Custom overrides active</strong>
            @if ($tenant->featureOverride?->expires_at ?? $tenant->limitOverride?->expires_at)
                · Expires {{ ($tenant->featureOverride?->expires_at ?? $tenant->limitOverride?->expires_at)?->format('M d, Y H:i') }}
            @endif
            @if ($tenant->featureOverride?->override_reason ?? $tenant->limitOverride?->override_reason)
                · {{ $tenant->featureOverride?->override_reason ?? $tenant->limitOverride?->override_reason }}
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('super-admin.tenant.features.update', $tenant->id) }}"
        @unless($canManage) onsubmit="return false;" @endunless>
        @csrf
        @method('PUT')
        @unless($canManage)
            <div class="alert alert-secondary">View only — ask an admin or owner to change plan overrides.</div>
        @endunless

        {{-- Override meta --}}
        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <div class="sa-card">
                    <div class="sa-card-header">
                        <h4 class="mb-0">Override notes</h4>
                    </div>
                    <div class="sa-card-body">
                        <div class="mb-3">
                            <label class="form-label">Reason (optional)</label>
                            <input type="text" name="override_reason" class="form-control"
                                value="{{ old('override_reason', $tenant->featureOverride?->override_reason ?? $tenant->limitOverride?->override_reason) }}"
                                placeholder="e.g. Enterprise deal, trial extension, support exception">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Override expires (optional)</label>
                            <input type="datetime-local" name="expires_at" class="form-control"
                                value="{{ old('expires_at', optional($tenant->featureOverride?->expires_at ?? $tenant->limitOverride?->expires_at)->format('Y-m-d\TH:i')) }}">
                            <div class="form-text">Leave empty until manually cleared. Applies to both limits and features below.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="sa-card h-100">
                    <div class="sa-card-body">
                        <h6 class="text-muted text-uppercase small mb-2">How this works</h6>
                        <ul class="small mb-0 ps-3">
                            <li><strong>Package default</strong> — from the subscribed pricing plan</li>
                            <li><strong>Used</strong> — live count in this tenant's CRM</li>
                            <li><strong>Override</strong> — force a custom limit or feature on/off</li>
                            <li>Use <code>-1</code> in limit overrides for unlimited</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Numeric limits (sellers, brands, etc.) --}}
        <div class="sa-card mb-4">
            <div class="sa-card-header">
                <h4 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Plan limits</h4>
            </div>
            <div class="sa-card-body p-0">
                @foreach ($limitGroups as $group => $limits)
                    <div class="px-3 pt-3 pb-1">
                        <h6 class="text-muted text-uppercase small mb-0">{{ $group }}</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table sa-table mb-0 sa-plan-table">
                            <thead>
                                <tr>
                                    <th>Limit</th>
                                    <th class="text-center">Package</th>
                                    <th class="text-center">Used</th>
                                    <th class="text-center">Effective</th>
                                    <th style="min-width: 140px">Override</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($limits as $limit)
                                    @php
                                        $limitOverrideVal = old(
                                            'limits.' . $limit['key'],
                                            $limit['override'] === null ? '' : $limit['override']
                                        );
                                    @endphp
                                    <tr class="{{ $limit['at_limit'] ? 'table-warning' : '' }}">
                                        <td data-label="Limit">
                                            <strong>{{ $limit['label'] }}</strong>
                                            <div class="small text-muted">{{ $limit['description'] }}</div>
                                            @if ($limit['at_limit'])
                                                <span class="badge bg-warning text-dark mt-1">At limit</span>
                                            @elseif ($limit['remaining'] !== null && $limit['effective'] > 0)
                                                <span class="small text-muted">{{ $limit['remaining'] }} remaining</span>
                                            @endif
                                        </td>
                                        <td data-label="Package" class="text-center">
                                            {{ \App\Support\TenantLimitCatalog::formatValue($limit['plan_default']) }}
                                        </td>
                                        <td data-label="Used" class="text-center">
                                            <strong>{{ number_format($limit['used']) }}</strong>
                                        </td>
                                        <td data-label="Effective" class="text-center">
                                            <span class="badge {{ $limit['effective'] === -1 ? 'bg-success' : 'bg-primary' }}">
                                                {{ \App\Support\TenantLimitCatalog::formatValue($limit['effective']) }}
                                            </span>
                                        </td>
                                        <td data-label="Override">
                                            <input type="number" name="limits[{{ $limit['key'] }}]"
                                                class="form-control form-control-sm"
                                                value="{{ $limitOverrideVal }}"
                                                placeholder="Package default"
                                                min="-1"
                                                step="1">
                                            <div class="form-text">Blank = package · -1 = unlimited</div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Boolean features --}}
        @foreach ($featureGroups as $group => $features)
            <div class="sa-card mb-4">
                <div class="sa-card-header">
                    <h4 class="mb-0"><i class="bi bi-toggles me-2"></i>{{ $group }}</h4>
                </div>
                <div class="sa-card-body p-0">
                    <div class="table-responsive">
                        <table class="table sa-table mb-0 sa-plan-table">
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    <th class="text-center">Package</th>
                                    <th class="text-center">Effective</th>
                                    <th style="min-width: 220px">Override</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($features as $feature)
                                    @php
                                        $overrideVal = old(
                                            'overrides.' . $feature['column'],
                                            $feature['override'] === null
                                                ? ''
                                                : ($feature['override'] ? '1' : '0')
                                        );
                                    @endphp
                                    <tr>
                                        <td data-label="Feature">
                                            <strong>{{ $feature['label'] }}</strong>
                                            <div class="small text-muted">{{ $feature['description'] }}</div>
                                        </td>
                                        <td data-label="Package" class="text-center">
                                            @if ($feature['plan_default'])
                                                <span class="badge bg-success">ON</span>
                                            @else
                                                <span class="badge bg-secondary">OFF</span>
                                            @endif
                                        </td>
                                        <td data-label="Effective" class="text-center">
                                            @if ($feature['effective'])
                                                <span class="badge bg-primary">ON</span>
                                            @else
                                                <span class="badge bg-light text-dark border">OFF</span>
                                            @endif
                                        </td>
                                        <td data-label="Override">
                                            <select name="overrides[{{ $feature['column'] }}]" class="form-select form-select-sm">
                                                <option value="" @selected($overrideVal === '')>Use package default</option>
                                                <option value="1" @selected($overrideVal === '1' || $overrideVal === 1 || $overrideVal === true)>Force ON</option>
                                                <option value="0" @selected($overrideVal === '0' || $overrideVal === 0 || $overrideVal === false)>Force OFF</option>
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

        @if ($canManage)
            <div class="text-end pb-4">
                <button type="submit" class="btn btn-sa-primary btn-lg">
                    <i class="bi bi-check2-circle me-1"></i> Save plan &amp; access settings
                </button>
            </div>
        @endif
    </form>

@endsection

@push('styles')
    <style>
        .sa-plan-table td { vertical-align: middle; }
        .sa-plan-table tbody tr:last-child td { border-bottom: 0; }
    </style>
@endpush
