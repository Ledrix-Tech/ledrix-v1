<header class="sa-topbar">
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="sa-menu-toggle" id="saMenuToggle" aria-label="Toggle menu">
            <i class="bi bi-list"></i>
        </button>
        <a href="{{ route('super-admin.index.get') }}" class="sa-topbar-brand">
            <img src="{{ asset(config('branding.logo')) }}" alt="Ledrix">
            <span class="d-none d-sm-inline">Super Admin</span>
        </a>
    </div>

    <div class="sa-topbar-actions">
        <div class="sa-clock d-none d-md-flex">
            <i class="bi bi-calendar3 me-1"></i>
            {{ now()->format('M d, Y') }}
            <strong class="ms-2" id="sa-live-time">{{ now()->format('H:i:s') }}</strong>
        </div>

        @php $user = Auth::guard('super_admin')->user(); @endphp
        <div class="dropdown">
            <button class="sa-user-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle"></i>
                <span>{{ ucfirst($user->name ?? 'Admin') }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end sa-user-menu">
                <li>
                    <a class="dropdown-item" href="{{ route('index.get') }}" target="_blank">
                        <i class="bi bi-box-arrow-up-right me-2"></i> View site
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="{{ route('super-admin.logout') }}">
                        <i class="bi bi-box-arrow-right me-2"></i> Log out
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
