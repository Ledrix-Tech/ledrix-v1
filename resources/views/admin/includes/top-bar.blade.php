<header class="crm-topbar">
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="crm-menu-toggle" id="crmMenuToggle" aria-label="Toggle menu">
            <i class="bi bi-list"></i>
        </button>
        <a href="{{ route('admin.index.get') }}" class="crm-topbar-brand">
            @php
                $brandLogo = config('branding.logo');
                if (($tenantHasWhiteLabel ?? false) && ! empty($tenantBrandLogo ?? null)) {
                    $brandLogo = 'storage/'.$tenantBrandLogo;
                }
            @endphp
            <img src="{{ asset($brandLogo) }}" alt="{{ ($tenantHasWhiteLabel ?? false) ? 'Workspace' : 'Ledrix' }}">
            <span class="crm-topbar-badge d-none d-sm-inline">CRM Admin</span>
        </a>
    </div>

    <div class="crm-topbar-actions">
        <div class="crm-clock d-none d-md-flex">
            <i class="bi bi-calendar3 me-1"></i>
            {{ now()->format('M d, Y') }}
            <strong class="ms-2" id="crm-live-time">{{ now()->format('H:i:s') }}</strong>
        </div>

        @php $user = Auth::guard('admin')->user() ?? Auth::guard('seller')->user(); @endphp
        <div class="dropdown">
            <button class="crm-user-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle"></i>
                <span class="d-none d-sm-inline">{{ ucfirst($user->name ?? 'User') }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end crm-user-menu">
                <li>
                    <a class="dropdown-item" href="{{ route('auth.profile.get') }}">
                        <i class="bi bi-person me-2"></i> Profile
                    </a>
                </li>
                @if (($user->role ?? null) === 'admin')
                <li>
                    <a class="dropdown-item" href="{{ route('admin.org.overview') }}">
                        <i class="bi bi-building me-2"></i> Organization
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('tenant.dashboard') }}" target="_blank" rel="noopener">
                        <i class="bi bi-box-arrow-up-right me-2"></i> Tenant portal
                    </a>
                </li>
                @endif
                <li>
                    <a class="dropdown-item" href="{{ route('index.get') }}" target="_blank">
                        <i class="bi bi-globe me-2"></i> View site
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('admin.logout') }}" class="d-inline">
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
