@php
    $route = request()->route()->getName();
@endphp
<aside class="sa-sidebar" id="saSidebar">
    <ul class="sa-nav">
        <li class="sa-nav-label">Main</li>
        <li>
            <a class="sa-nav-link {{ $route === 'super-admin.index.get' ? 'active' : '' }}"
                href="{{ route('super-admin.index.get') }}">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a class="sa-nav-link {{ $route === 'super-admin.contact-queries.get' ? 'active' : '' }}"
                href="{{ route('super-admin.contact-queries.get') }}">
                <i class="bi bi-chat-left-text"></i>
                <span>Contact Queries</span>
            </a>
        </li>
        <li>
            <a class="sa-nav-link {{ $route === 'super-admin.pricing-packages.get' ? 'active' : '' }}"
                href="{{ route('super-admin.pricing-packages.get') }}">
                <i class="bi bi-tags"></i>
                <span>Pricing Packages</span>
            </a>
        </li>
        <li>
            <a class="sa-nav-link {{ in_array($route, ['super-admin.company-profile.get', 'super-admin.tenant.show']) ? 'active' : '' }}"
                href="{{ route('super-admin.company-profile.get') }}">
                <i class="bi bi-buildings"></i>
                <span>Tenants</span>
            </a>
        </li>
        <li>
            <a class="sa-nav-link {{ $route === 'super-admin.subscription-payments.get' ? 'active' : '' }}"
                href="{{ route('super-admin.subscription-payments.get') }}">
                <i class="bi bi-wallet2"></i>
                <span>Subscription Payments</span>
            </a>
        </li>
    </ul>
</aside>
