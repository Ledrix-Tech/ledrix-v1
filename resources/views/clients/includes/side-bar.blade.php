@php
    $route = request()->route()?->getName() ?? '';
    $user = auth()->guard('client')->user();
@endphp
<aside class="crm-sidebar" id="crmSidebar">
    <div class="crm-sidebar-brand-dots">
        <span class="crm-dot crm-dot-purple" title="Ledrix"></span>
        <span class="crm-dot crm-dot-green" title="Ledrix"></span>
    </div>
    <div class="crm-sidebar-tagline">Client Workspace</div>

    <ul class="crm-nav">
        <li class="crm-nav-label">Menu</li>
        <li>
            <a class="crm-nav-link {{ $route === 'client.index.get' ? 'active' : '' }}"
                href="{{ route('client.index.get') }}">
                <i class="bi bi-speedometer2"></i><span>Dashboard</span>
            </a>
        </li>
        <li>
            <a class="crm-nav-link {{ $route === 'client.brief.get' ? 'active' : '' }}"
                href="{{ route('client.brief.get') }}">
                <i class="bi bi-journal-text"></i><span>Briefs</span>
            </a>
        </li>
        <li>
            <a class="crm-nav-link {{ $route === 'client.invoice.get' || str_starts_with($route, 'client.invoice.') ? 'active' : '' }}"
                href="{{ route('client.invoice.get') }}">
                <i class="bi bi-receipt"></i><span>Invoices</span>
            </a>
        </li>
        <li>
            <a class="crm-nav-link {{ in_array($route, ['client.raised-tickets.get', 'client.raise-ticket.get']) ? 'active' : '' }}"
                href="{{ route('client.raised-tickets.get') }}">
                <i class="bi bi-life-preserver"></i><span>Tickets</span>
            </a>
        </li>
        {{-- Messages: enable when messaging is implemented
        <li>
            <a class="crm-nav-link {{ $route === 'client.messages.get' ? 'active' : '' }}"
                href="{{ route('client.messages.get') }}">
                <i class="bi bi-chat-dots"></i><span>Messages</span>
            </a>
        </li>
        --}}
    </ul>

    <div class="crm-sidebar-footer">
        <div class="crm-sidebar-user">
            <i class="bi bi-person-circle"></i>
            <div>
                <div>{{ $user->name ?? 'Client' }}</div>
                <small>{{ $user->email ?? '' }}</small>
            </div>
        </div>
    </div>
</aside>
