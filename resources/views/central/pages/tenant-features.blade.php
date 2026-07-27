@extends('central.layout.layout')

@section('title', 'Ledrix | Tenant Features')

@section('central-content')

    <div class="sa-page-header">
        <div>
            <a href="{{ route('super-admin.tenant.show', $tenant->id) }}" class="sa-back">&larr; Back to tenant</a>
            <h1>Feature access — {{ $tenant->name }}</h1>
            <p>
                Plan: <strong>{{ $tenant->plan?->name ?? 'None' }}</strong>
                · Overrides apply on top of package defaults
            </p>
        </div>
        @if ($hasOverrides)
            <form method="POST" action="{{ route('super-admin.tenant.features.reset', $tenant->id) }}"
                onsubmit="return confirm('Clear all overrides and revert to plan defaults?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reset to plan defaults
                </button>
            </form>
        @endif
    </div>

    @if ($tenant->featureOverride && ! $tenant->featureOverride->isExpired())
        <div class="alert alert-info">
            <strong>Active override</strong>
            @if ($tenant->featureOverride->expires_at)
                · Expires {{ $tenant->featureOverride->expires_at->format('M d, Y H:i') }}
            @endif
            @if ($tenant->featureOverride->override_reason)
                · {{ $tenant->featureOverride->override_reason }}
            @endif
            @if ($tenant->featureOverride->overriddenBy)
                · By {{ $tenant->featureOverride->overriddenBy->name ?? 'Super admin' }}
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('super-admin.tenant.features.update', $tenant->id) }}">
        @csrf
        @method('PUT')

        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <div class="sa-card">
                    <div class="sa-card-header">
                        <h4 class="mb-0">Override settings</h4>
                    </div>
                    <div class="sa-card-body">
                        <div class="mb-3">
                            <label class="form-label">Reason (optional)</label>
                            <input type="text" name="override_reason" class="form-control"
                                value="{{ old('override_reason', $tenant->featureOverride?->override_reason) }}"
                                placeholder="e.g. Trial extension, enterprise deal, support exception">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Override expires (optional)</label>
                            <input type="datetime-local" name="expires_at" class="form-control"
                                value="{{ old('expires_at', optional($tenant->featureOverride?->expires_at)->format('Y-m-d\TH:i')) }}">
                            <div class="form-text">Leave empty for permanent override until cleared.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="sa-card h-100">
                    <div class="sa-card-body">
                        <h6 class="text-muted text-uppercase small mb-2">How overrides work</h6>
                        <ul class="small mb-0 ps-3">
                            <li><strong>Use plan default</strong> — follows the subscription package</li>
                            <li><strong>Force ON</strong> — enabled even if plan says off</li>
                            <li><strong>Force OFF</strong> — disabled even if plan says on</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        @foreach ($featureGroups as $group => $features)
            <div class="sa-card mb-4">
                <div class="sa-card-header">
                    <h4 class="mb-0">{{ $group }}</h4>
                </div>
                <div class="sa-card-body p-0">
                    <div class="table-responsive">
                        <table class="table sa-table mb-0 sa-feature-table">
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    <th class="text-center">Plan default</th>
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
                                        <td data-label="Plan" class="text-center">
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
                                                <option value="" @selected($overrideVal === '')>Use plan default</option>
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

        <div class="text-end">
            <button type="submit" class="btn btn-sa-primary btn-lg">
                <i class="bi bi-check2-circle me-1"></i> Save feature overrides
            </button>
        </div>
    </form>

@endsection

@push('styles')
    <style>
        .sa-feature-table td { vertical-align: middle; }
    </style>
@endpush
