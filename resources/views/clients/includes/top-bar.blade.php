@php
    $user = auth()->guard('client')->user();
    $homeRoute = route('client.index.get');
@endphp
<header class="crm-topbar">
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="crm-menu-toggle" id="crmMenuToggle" aria-label="Toggle menu">
            <i class="bi bi-list"></i>
        </button>
        <a href="{{ $homeRoute }}" class="crm-topbar-brand">
            <img src="{{ asset(config('branding.logo')) }}" alt="Ledrix">
            <span class="crm-topbar-badge d-none d-sm-inline">Client Portal</span>
        </a>
    </div>

    <div class="crm-topbar-actions">
        <div class="crm-clock d-none d-md-flex">
            <i class="bi bi-calendar3 me-1"></i>
            {{ now()->format('M d, Y') }}
            <strong class="ms-2" id="crm-live-time">{{ now()->format('H:i:s') }}</strong>
        </div>

        <div class="dropdown">
            <button class="crm-user-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle"></i>
                <span class="d-none d-sm-inline">{{ ucfirst($user->name ?? 'Client') }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end crm-user-menu">
                <li>
                    <a class="dropdown-item" href="{{ route('client.profile.get') }}">
                        <i class="bi bi-person me-2"></i> Profile
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('client.logout') }}" class="d-inline">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i> Log out
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
</header>
