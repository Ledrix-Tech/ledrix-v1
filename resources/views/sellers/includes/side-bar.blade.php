@php
    $route = request()->route()?->getName() ?? '';
    $user = Auth::guard('seller')->user();
@endphp
<aside class="crm-sidebar" id="crmSidebar">
    <div class="crm-sidebar-brand-dots">
        <span class="crm-dot crm-dot-purple" title="Ledrix"></span>
        <span class="crm-dot crm-dot-green" title="Ledrix"></span>
    </div>
    <div class="crm-sidebar-tagline">Sales Workspace</div>

    <ul class="crm-nav">
        <li class="crm-nav-label">Menu</li>

        @if (isProjectManager())
            <li>
                <a class="crm-nav-link {{ $route === 'seller.index.get' ? 'active' : '' }}"
                    href="{{ route('seller.index.get') }}">
                    <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                </a>
            </li>
            <li>
                <a class="crm-nav-link {{ $route === 'seller.seller-performance.get' ? 'active' : '' }}"
                    href="{{ route('seller.seller-performance.get') }}">
                    <i class="bi bi-graph-up-arrow"></i><span>Performance</span>
                </a>
            </li>
            <li>
                <a class="crm-nav-link {{ $route === 'seller.clients.get' ? 'active' : '' }}"
                    href="{{ route('seller.clients.get') }}">
                    <i class="bi bi-people"></i><span>Clients</span>
                </a>
            </li>
            <li>
                <a class="crm-nav-link {{ in_array($route, ['seller.briefs.get', 'seller.client-briefs.get']) ? 'active' : '' }}"
                    href="{{ route('seller.briefs.get') }}">
                    <i class="bi bi-journal-text"></i><span>Briefs</span>
                </a>
            </li>
            <li>
                <a class="crm-nav-link {{ $route === 'seller.brands.get' ? 'active' : '' }}"
                    href="{{ route('seller.brands.get') }}">
                    <i class="bi bi-globe"></i><span>Brands</span>
                </a>
            </li>
            <li>
                <a class="crm-nav-link {{ $route === 'seller.sellers.get' ? 'active' : '' }}"
                    href="{{ route('seller.sellers.get') }}">
                    <i class="bi bi-person-badge"></i><span>Sellers</span>
                </a>
            </li>
            @if ($tenantHasSellerLeaderboard ?? false)
                <li>
                    <a class="crm-nav-link {{ $route === 'seller.seller-leaderboard.get' ? 'active' : '' }}"
                        href="{{ route('seller.seller-leaderboard.get') }}">
                        <i class="bi bi-trophy"></i><span>Leaderboard</span>
                    </a>
                </li>
            @endif
            <li>
                <a class="crm-nav-link {{ $route === 'seller.assigned-leads.get' ? 'active' : '' }}"
                    href="{{ route('seller.assigned-leads.get') }}">
                    <i class="bi bi-basket"></i><span>Assigned Leads</span>
                </a>
            </li>
            <li>
                <a class="crm-nav-link {{ $route === 'seller.assigned-leads-orders.get' ? 'active' : '' }}"
                    href="{{ route('seller.assigned-leads-orders.get') }}">
                    <i class="bi bi-receipt"></i><span>Orders</span>
                </a>
            </li>
        @else
            <li>
                <a class="crm-nav-link {{ $route === 'seller.seller-performance.get' ? 'active' : '' }}"
                    href="{{ route('seller.seller-performance.get') }}">
                    <i class="bi bi-graph-up-arrow"></i><span>Performance</span>
                </a>
            </li>
            <li>
                <a class="crm-nav-link {{ $route === 'seller.index.get' ? 'active' : '' }}"
                    href="{{ route('seller.index.get') }}">
                    <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                </a>
            </li>
            <li>
                <a class="crm-nav-link {{ $route === 'seller.clients.get' ? 'active' : '' }}"
                    href="{{ route('seller.clients.get') }}">
                    <i class="bi bi-people"></i><span>Clients</span>
                </a>
            </li>
            <li>
                <a class="crm-nav-link {{ in_array($route, ['seller.briefs.get', 'seller.client-briefs.get']) ? 'active' : '' }}"
                    href="{{ route('seller.briefs.get') }}">
                    <i class="bi bi-journal-text"></i><span>Briefs</span>
                </a>
            </li>
            <li>
                <a class="crm-nav-link {{ $route === 'seller.brands.get' ? 'active' : '' }}"
                    href="{{ route('seller.brands.get') }}">
                    <i class="bi bi-globe"></i><span>Brands</span>
                </a>
            </li>
            <li>
                <a class="crm-nav-link {{ $route === 'seller.sellers.get' ? 'active' : '' }}"
                    href="{{ route('seller.sellers.get') }}">
                    <i class="bi bi-person-badge"></i><span>Sellers</span>
                </a>
            </li>
            @if ($tenantHasSellerLeaderboard ?? false)
                <li>
                    <a class="crm-nav-link {{ $route === 'seller.seller-leaderboard.get' ? 'active' : '' }}"
                        href="{{ route('seller.seller-leaderboard.get') }}">
                        <i class="bi bi-trophy"></i><span>Leaderboard</span>
                    </a>
                </li>
            @endif
            <li>
                <a class="crm-nav-link {{ in_array($route, ['seller.leads.get', 'seller.lead-details.get']) ? 'active' : '' }}"
                    href="{{ route('seller.leads.get') }}">
                    <i class="bi bi-funnel"></i><span>Leads</span>
                </a>
            </li>
            <li>
                <a class="crm-nav-link {{ in_array($route, ['seller.orders.get', 'seller.renewed-orders.get']) ? 'active' : '' }}"
                    href="{{ route('seller.orders.get') }}">
                    <i class="bi bi-receipt"></i><span>Orders</span>
                </a>
            </li>
            @if ($tenantHasPayments ?? false)
                <li>
                    <a class="crm-nav-link {{ $route === 'seller.payments.get' ? 'active' : '' }}"
                        href="{{ route('seller.payments.get') }}">
                        <i class="bi bi-wallet2"></i><span>Payments</span>
                    </a>
                </li>
            @endif
        @endif
    </ul>

    <div class="crm-sidebar-footer">
        <div class="crm-sidebar-user">
            <i class="bi bi-person-circle"></i>
            <div>
                <strong>{{ ucfirst($user->name ?? 'Seller') }}</strong><br>
                <small>{{ isProjectManager() ? 'Project Manager' : 'Front Seller' }}</small>
            </div>
        </div>
    </div>
</aside>
