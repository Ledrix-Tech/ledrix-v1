@php
    $route = request()->route()->getName();
    $sa = auth('super_admin')->user();
    $isOwner = $sa?->isOwner() ?? false;
    $isAdmin = $sa?->isAdmin() ?? false;
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
            <a class="sa-nav-link {{ $route === 'super-admin.demo-requests.get' ? 'active' : '' }}"
                href="{{ route('super-admin.demo-requests.get') }}">
                <i class="bi bi-easel2"></i>
                <span>Demo Requests</span>
            </a>
        </li>
        @if ($isAdmin)
            <li>
                <a class="sa-nav-link {{ $route === 'super-admin.pricing-packages.get' ? 'active' : '' }}"
                    href="{{ route('super-admin.pricing-packages.get') }}">
                    <i class="bi bi-tags"></i>
                    <span>Pricing Packages</span>
                </a>
            </li>
        @endif
        <li>
            <a class="sa-nav-link {{ in_array($route, ['super-admin.company-profile.get', 'super-admin.tenant.show', 'super-admin.tenant.features.get']) ? 'active' : '' }}"
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
        <li>
            <a class="sa-nav-link {{ $route === 'super-admin.renewal-requests.get' ? 'active' : '' }}"
                href="{{ route('super-admin.renewal-requests.get') }}">
                <i class="bi bi-arrow-repeat"></i>
                <span>Renewal Requests</span>
            </a>
        </li>
        @if ($isAdmin)
            <li>
                <a class="sa-nav-link {{ $route === 'super-admin.billing-settings.get' ? 'active' : '' }}"
                    href="{{ route('super-admin.billing-settings.get') }}">
                    <i class="bi bi-key"></i>
                    <span>Payment Accounts</span>
                </a>
            </li>
        @endif
        <li>
            <a class="sa-nav-link {{ $route === 'super-admin.audit-logs.get' ? 'active' : '' }}"
                href="{{ route('super-admin.audit-logs.get') }}">
                <i class="bi bi-journal-text"></i>
                <span>Audit Logs</span>
            </a>
        </li>
        <li>
            <a class="sa-nav-link {{ str_starts_with((string) $route, 'super-admin.data-exports') ? 'active' : '' }}"
                href="{{ route('super-admin.data-exports.get') }}">
                <i class="bi bi-file-earmark-zip"></i>
                <span>Data Exports</span>
            </a>
        </li>
        <li>
            <a class="sa-nav-link {{ $route === 'super-admin.announcements.get' ? 'active' : '' }}"
                href="{{ route('super-admin.announcements.get') }}">
                <i class="bi bi-megaphone"></i>
                <span>Announcements</span>
            </a>
        </li>
        <li>
            <a class="sa-nav-link {{ in_array($route, ['super-admin.support-tickets.get', 'super-admin.support-tickets.show']) ? 'active' : '' }}"
                href="{{ route('super-admin.support-tickets.get') }}">
                <i class="bi bi-life-preserver"></i>
                <span>Support Tickets</span>
            </a>
        </li>

        <li class="sa-nav-label">Growth</li>
        <li>
            <a class="sa-nav-link {{ $route === 'super-admin.referrals.get' ? 'active' : '' }}"
                href="{{ route('super-admin.referrals.get') }}">
                <i class="bi bi-gift"></i>
                <span>Referrals</span>
            </a>
        </li>

        <li class="sa-nav-label">Platform</li>
        <li>
            <a class="sa-nav-link {{ $route === 'super-admin.webhook-events.get' ? 'active' : '' }}"
                href="{{ route('super-admin.webhook-events.get') }}">
                <i class="bi bi-broadcast"></i>
                <span>Webhook Events</span>
            </a>
        </li>
        @if ($isOwner)
            <li>
                <a class="sa-nav-link {{ $route === 'super-admin.team.get' ? 'active' : '' }}"
                    href="{{ route('super-admin.team.get') }}">
                    <i class="bi bi-people"></i>
                    <span>Team</span>
                </a>
            </li>
        @endif
        <li>
            <a class="sa-nav-link {{ str_starts_with($route, 'super-admin.2fa') ? 'active' : '' }}"
                href="{{ route('super-admin.2fa.setup') }}">
                <i class="bi bi-shield-lock"></i>
                <span>2FA Security</span>
            </a>
        </li>
    </ul>
</aside>
