@extends('central.layout.layout')

@section('title', 'Ledrix | Tenants')

@section('central-content')

    <div class="sa-page-header">
        <div>
            <h1>Tenants</h1>
            <p>All registered tenant accounts</p>
        </div>
    </div>

    <div class="sa-card">
        <div class="sa-card-body p-0">
            <div class="sa-table-wrap">
                <table class="table sa-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tenant</th>
                            <th>Email</th>
                            <th>Plan</th>
                            <th>Trial</th>
                            <th>Verified</th>
                            <th>Registered</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companies as $company)
                            <tr>
                                <td data-label="#">{{ $company->id }}</td>
                                <td data-label="Tenant">
                                    <strong>{{ $company->name ?? '—' }}</strong><br>
                                    <small class="text-muted">{{ $company->slug ?? '—' }}</small>
                                    @if ($company->website)
                                        <br><a href="{{ $company->website }}" target="_blank" class="small">{{ $company->website }}</a>
                                    @endif
                                </td>
                                <td data-label="Email">{{ $company->email ?? '—' }}</td>
                                <td data-label="Plan">{{ $company->plan?->name ?? ($company->activeMembership?->plan?->name ?? '—') }}</td>
                                <td data-label="Trial">
                                    @if ($company->isOnTrial())
                                        <span class="badge bg-info">{{ $company->trialDaysLeft() }} days left</span>
                                    @elseif ($company->trial_used)
                                        <span class="text-muted">Used</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Verified">
                                    @if ($company->email_verified_at)
                                        <span class="badge bg-success">Verified</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pending</span>
                                    @endif
                                </td>
                                <td data-label="Registered">{{ $company->created_at?->format('M d, Y') ?? '—' }}</td>
                                <td data-label="Source">
                                    @php $signupSource = data_get($company->meta, 'registered_from'); @endphp
                                    @if ($signupSource)
                                        <span class="badge bg-info text-dark">{{ str_replace('_', ' ', $signupSource) }}</span>
                                        @if (data_get($company->meta, 'landing_path'))
                                            <br><small class="text-muted">{{ data_get($company->meta, 'landing_path') }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td data-label="Status">
                                    @php
                                        $statusClass = match ($company->status) {
                                            'active' => 'success',
                                            'pending_email' => 'warning',
                                            'suspended', 'inactive' => 'danger',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $company->status)) }}</span>
                                </td>
                                <td data-label="Actions">
                                    <div class="d-flex flex-wrap gap-1 justify-content-end">
                                        <a href="{{ route('super-admin.tenant.show', $company->id) }}" class="btn btn-sm btn-outline-primary">Details</a>
                                        <a href="{{ route('super-admin.tenant.features.get', $company->id) }}" class="btn btn-sm btn-outline-secondary">Plan &amp; access</a>
                                        @if ($company->status === 'active')
                                            <button type="button" class="btn btn-sm btn-outline-danger sa-tenant-status-btn"
                                                data-id="{{ $company->id }}" data-status="suspended">Suspend</button>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-success sa-tenant-status-btn"
                                                data-id="{{ $company->id }}" data-status="active">Activate</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No tenants found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($companies->hasPages())
                <div class="sa-pagination">{{ $companies->links() }}</div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        window.SaTenantConfig = {
            statusUrl: @json(route('super-company.company-status')),
            csrf: @json(csrf_token()),
        };
    </script>
    <script src="{{ asset('super-admin-assets/js/tenants.js') }}" defer></script>
@endpush
