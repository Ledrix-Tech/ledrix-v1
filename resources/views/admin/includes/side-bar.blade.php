@php
    $route = request()->route()?->getName() ?? '';
    $user = Auth::guard('admin')->user() ?? Auth::guard('seller')->user();
@endphp
<aside class="crm-sidebar" id="crmSidebar">
    <div class="crm-sidebar-brand-dots">
        <span class="crm-dot crm-dot-purple" title="Ledrix"></span>
        <span class="crm-dot crm-dot-green" title="Ledrix"></span>
    </div>
    <div class="crm-sidebar-tagline">CRM Workspace</div>

    <ul class="crm-nav">
        <li class="crm-nav-label">Menu</li>

        @if (isFinance())
            <li>
                <a class="crm-nav-link {{ $route === 'admin.brand-payments.get' ? 'active' : '' }}"
                    href="{{ route('admin.brand-payments.get') }}">
                    <i class="bi bi-credit-card"></i><span>Brand Payments</span>
                </a>
            </li>
            <li>
                <a class="crm-nav-link {{ $route === 'admin.brand-payouts.get' ? 'active' : '' }}"
                    href="{{ route('admin.brand-payouts.get') }}">
                    <i class="bi bi-cash-stack"></i><span>Brand Payouts</span>
                </a>
            </li>
        @else
            <li>
                <a class="crm-nav-link {{ $route === 'admin.index.get' ? 'active' : '' }}"
                    href="{{ route('admin.index.get') }}">
                    <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                </a>
            </li>
            @if (isAdmin())
                @if ($tenantHasPayments ?? false)
                <li>
                    <a class="crm-nav-link {{ $route === 'admin.account-keys.get' ? 'active' : '' }}"
                        href="{{ route('admin.account-keys.get') }}">
                        <i class="bi bi-key"></i><span>Account Keys</span>
                    </a>
                </li>
                @endif
                @if ($tenantHasApiAccess ?? false)
                <li>
                    <a class="crm-nav-link {{ $route === 'admin.domain-script.get' ? 'active' : '' }}"
                        href="{{ route('admin.domain-script.get') }}">
                        <i class="bi bi-code-slash"></i><span>Script</span>
                    </a>
                </li>
                @endif
            @endif
            <li>
                <a class="crm-nav-link {{ $route === 'admin.clients.get' ? 'active' : '' }}"
                    href="{{ route('admin.clients.get') }}">
                    <i class="bi bi-people"></i><span>Clients</span>
                </a>
            </li>
            @if (isAdmin())
                <li>
                    <a class="crm-nav-link {{ $route === 'admin.brands.get' ? 'active' : '' }}"
                        href="{{ route('admin.brands.get') }}">
                        <i class="bi bi-globe"></i><span>Brands</span>
                    </a>
                </li>
                <li>
                    <a class="crm-nav-link {{ $route === 'admin.sellers.get' ? 'active' : '' }}"
                        href="{{ route('admin.sellers.get') }}">
                        <i class="bi bi-person-badge"></i><span>Sellers</span>
                    </a>
                </li>
            @endif

            @if (isProjectManager())
                <li>
                    <a class="crm-nav-link {{ $route === 'admin.assigned-leads.get' ? 'active' : '' }}"
                        href="{{ route('admin.assigned-leads.get') }}">
                        <i class="bi bi-basket"></i><span>Assigned Leads</span>
                    </a>
                </li>
                <li>
                    <a class="crm-nav-link {{ in_array($route, ['admin.assigned-leads-orders.get', 'admin.renewed-orders.get']) ? 'active' : '' }}"
                        href="{{ route('admin.assigned-leads-orders.get') }}">
                        <i class="bi bi-receipt"></i><span>Orders</span>
                    </a>
                </li>
            @else
                <li>
                    <a class="crm-nav-link {{ in_array($route, ['admin.leads.get', 'admin.lead-details.get']) ? 'active' : '' }}"
                        href="{{ route('admin.leads.get') }}">
                        <i class="bi bi-funnel"></i><span>Leads</span>
                    </a>
                </li>
                @if (isAdmin())
                    <li>
                        <a class="crm-nav-link {{ in_array($route, ['admin.orders.get', 'admin.renewed-orders.get']) ? 'active' : '' }}"
                            href="{{ route('admin.orders.get') }}">
                            <i class="bi bi-receipt"></i><span>Orders</span>
                        </a>
                    </li>
                    @if ($tenantHasPayments ?? false)
                    <li>
                        <a class="crm-nav-link {{ $route === 'admin.payments.get' ? 'active' : '' }}"
                            href="{{ route('admin.payments.get') }}">
                            <i class="bi bi-wallet2"></i><span>Payments</span>
                        </a>
                    </li>
                    @endif
                    @if ($tenantHasUpworkModule ?? false)
                    <li>
                        <a class="crm-nav-link {{ $route === 'admin.upwork-clients.get' ? 'active' : '' }}"
                            href="{{ route('admin.upwork-clients.get') }}">
                            <i class="bi bi-people"></i><span>Upwork clients</span>
                        </a>
                    </li>
                    <li>
                        <a class="crm-nav-link {{ $route === 'admin.upwork-orders.get' ? 'active' : '' }}"
                            href="{{ route('admin.upwork-orders.get') }}">
                            <i class="bi bi-briefcase"></i><span>Upwork orders</span>
                        </a>
                    </li>
                    <li>
                        <a class="crm-nav-link {{ $route === 'admin.upwork-payments.get' ? 'active' : '' }}"
                            href="{{ route('admin.upwork-payments.get') }}">
                            <i class="bi bi-cash-coin"></i><span>Upwork payments</span>
                        </a>
                    </li>
                    @endif
                    <li>
                        <a class="crm-nav-link {{ $route === 'admin.org.overview' ? 'active' : '' }}"
                            href="{{ route('admin.org.overview') }}">
                            <i class="bi bi-building"></i><span>Organization</span>
                        </a>
                    </li>
                    <li>
                        <a class="crm-nav-link {{ $route === 'admin.org.plan' ? 'active' : '' }}"
                            href="{{ route('admin.org.plan') }}">
                            <i class="bi bi-grid-3x3-gap"></i><span>Plan &amp; features</span>
                        </a>
                    </li>
                    <li>
                        <a class="crm-nav-link {{ str_starts_with($route, 'admin.org.settings') ? 'active' : '' }}"
                            href="{{ route('admin.org.settings') }}">
                            <i class="bi bi-gear"></i><span>Org settings</span>
                        </a>
                    </li>
                    @if (($tenantHasCustomDomain ?? false) || ($tenantHasWhiteLabel ?? false))
                    <li>
                        <a class="crm-nav-link {{ str_starts_with($route, 'admin.org.domain') ? 'active' : '' }}"
                            href="{{ route('admin.org.domain') }}">
                            <i class="bi bi-globe2"></i><span>Domain &amp; brand</span>
                        </a>
                    </li>
                    @endif
                    <li>
                        <a class="crm-nav-link {{ str_starts_with($route, 'admin.org.audit-logs') ? 'active' : '' }}"
                            href="{{ route('admin.org.audit-logs') }}">
                            <i class="bi bi-journal-text"></i><span>Audit log</span>
                        </a>
                    </li>
                    <li>
                        <a class="crm-nav-link {{ str_starts_with($route, 'admin.org.team') ? 'active' : '' }}"
                            href="{{ route('admin.org.team') }}">
                            <i class="bi bi-people"></i><span>Team</span>
                        </a>
                    </li>
                    <li>
                        <a class="crm-nav-link {{ str_starts_with($route, 'admin.org.billing') ? 'active' : '' }}"
                            href="{{ route('admin.org.billing') }}">
                            <i class="bi bi-credit-card"></i><span>Billing</span>
                        </a>
                    </li>
                    <li>
                        <a class="crm-nav-link {{ str_starts_with($route, 'admin.org.support') ? 'active' : '' }}"
                            href="{{ route('admin.org.support.index') }}">
                            <i class="bi bi-headset"></i><span>Support</span>
                        </a>
                    </li>
                    <li>
                        <a class="crm-nav-link {{ str_starts_with($route, 'admin.org.referrals') ? 'active' : '' }}"
                            href="{{ route('admin.org.referrals') }}">
                            <i class="bi bi-gift"></i><span>Referrals</span>
                        </a>
                    </li>
                    @if ($tenantHasApiAccess ?? false)
                    <li>
                        <a class="crm-nav-link {{ str_starts_with($route, 'admin.org.api-tokens') ? 'active' : '' }}"
                            href="{{ route('admin.org.api-tokens') }}">
                            <i class="bi bi-key"></i><span>API tokens</span>
                        </a>
                    </li>
                    @endif
                @else
                    <li>
                        <a class="crm-nav-link {{ in_array($route, ['admin.assigned-leads-orders.get', 'admin.renewed-orders.get']) ? 'active' : '' }}"
                            href="{{ route('admin.assigned-leads-orders.get') }}">
                            <i class="bi bi-receipt"></i><span>Orders</span>
                        </a>
                    </li>
                @endif
            @endif
        @endif
    </ul>

    <div class="crm-sidebar-footer">
        <div class="crm-sidebar-user">
            <i class="bi bi-person-circle"></i>
            <div>
                <strong>{{ ucfirst($user->name ?? 'Guest') }}</strong><br>
                <small>{{ ucfirst($user->role ?? 'User') }}</small>
            </div>
        </div>
    </div>
</aside>
