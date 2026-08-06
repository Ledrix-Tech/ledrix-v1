
@php $isAdminOrg = ($organizationPortal ?? 'tenant') === 'admin'; @endphp
    @unless ($isAdminOrg)
    <header class="hero d-flex align-items-center justify-content-center text-center"
        style="background: linear-gradient(rgba(0,0,0,.5), rgba(0,0,0,.5)), url('https://images.ctfassets.net/px6a31ta05xu/wp-media-78750/418b7767647f5cf9cffc5d76dd304d04/CAP-US-Header-10-CRM-Features-and-Why-You-Need-Them-1200x400-DLVR_US_1200x400_DLVR.png') no-repeat center center; background-size: cover; min-height: 180px;">
        <div class="container text-white">
            <h1>Referrals</h1>
            <p class="mb-0">Share Ledrix and earn account credit when friends subscribe</p>
        </div>
    </header>
    @endunless

    <main class="{{ $isAdminOrg ? 'pb-4' : 'py-5' }}">
        <div class="{{ $isAdminOrg ? '' : 'container' }}" style="{{ $isAdminOrg ? '' : 'max-width: 960px;' }}">
            @if ($isAdminOrg)
                <div class="crm-page-header mb-4">
                    <div>
                        <h1>Referrals</h1>
                        <p>Share Ledrix and earn account credit when friends subscribe.</p>
                    </div>
                    <div class="crm-page-actions">
                        <a href="{{ org_route('billing') }}" class="btn btn-outline-primary btn-sm">Billing</a>
                        <form method="POST" action="{{ org_route('referrals.issue') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-crm-primary btn-sm">Create code</button>
                        </form>
                    </div>
                </div>
            @else
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <a href="{{ org_route('dashboard') }}" class="btn btn-outline-secondary btn-sm">&larr; Dashboard</a>
                <div class="d-flex gap-2">
                    <a href="{{ org_route('billing') }}" class="btn btn-outline-primary btn-sm">Billing</a>
                    <form method="POST" action="{{ org_route('referrals.issue') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">Create code</button>
                    </form>
                </div>
            </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Account credit</h6>
                            @if (count($credits))
                                @foreach ($credits as $currency => $amount)
                                    <p class="mb-1 fs-4 fw-semibold">{{ $currency }} {{ number_format($amount, $currency === 'USD' ? 2 : 0) }}</p>
                                @endforeach
                                <small class="text-muted">Applied automatically on your next invoice in matching currency.</small>
                            @else
                                <p class="mb-0 text-muted">No credit yet. Convert a referral, then ask support to mark it rewarded — or wait for ops to reward converted codes.</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-muted mb-2">Pending discount</h6>
                            @if ($pendingDiscount)
                                <p class="mb-0 fs-5 fw-semibold">
                                    @if (($pendingDiscount['type'] ?? '') === 'percent')
                                        {{ rtrim(rtrim(number_format((float) $pendingDiscount['value'], 2), '0'), '.') }}% off next invoice
                                    @else
                                        {{ strtoupper($pendingDiscount['currency'] ?? $billingCurrency) }}
                                        {{ number_format((float) ($pendingDiscount['value'] ?? 0), 2) }} off
                                    @endif
                                </p>
                            @else
                                <p class="mb-0 text-muted">None queued.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Your codes</strong>
                    <span class="badge bg-light text-dark border">{{ $referrals->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Share link</th>
                                <th>Reward</th>
                                <th>Status</th>
                                <th>Referred</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($referrals as $referral)
                                @php
                                    $shareUrl = $defaultSlug
                                        ? route('tenant.register.form', ['slug' => $defaultSlug, 'ref' => $referral->referral_code])
                                        : route('pricing.get', ['ref' => $referral->referral_code]);
                                @endphp
                                <tr>
                                    <td><code>{{ $referral->referral_code }}</code></td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" readonly value="{{ $shareUrl }}"
                                            onclick="this.select()">
                                    </td>
                                    <td>
                                        {{ ucfirst($referral->reward_type) }}
                                        <br><small class="text-muted">{{ strtoupper($referral->currency) }} {{ number_format((float) $referral->reward_amount, 2) }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match ($referral->status) {
                                                'pending' => 'warning',
                                                'converted' => 'info',
                                                'rewarded' => 'success',
                                                'expired' => 'secondary',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}">{{ ucfirst($referral->status) }}</span>
                                        @if ($referral->expires_at)
                                            <br><small class="text-muted">Expires {{ $referral->expires_at->format('M d, Y') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($referral->referred)
                                            {{ $referral->referred->name }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No referral codes yet. Click <strong>Create code</strong> to get started.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
