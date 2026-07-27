<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        <li class="nav-item nav-category">Main</li>
        <li class="nav-item {{ request()->route()->getName() === 'upwork.index.get' ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('upwork.index.get') }}">
                <span class="icon-bg"><i class="mdi mdi-cube menu-icon"></i></span>
                <span class="menu-title">Dashboard</span>
            </a>
        </li>
        <li class="nav-item {{ request()->route()->getName() === 'upwork.link-generator.get' ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('upwork.link-generator.get') }}">
                <span class="icon-bg"><i class="mdi mdi-link menu-icon"></i></span>
                <span class="menu-title">Link Generator</span>
            </a>
        </li>
        <li class="nav-item {{ request()->route()->getName() === 'upwork.clients.get' ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('upwork.clients.get') }}">
                <span class="icon-bg"><i class="mdi mdi-account-group menu-icon"></i></span>
                <span class="menu-title">Clients</span>
            </a>
        </li>
        <li class="nav-item {{ request()->route()->getName() === 'upwork.orders.get' ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('upwork.orders.get') }}">
                <span class="icon-bg"><i class="mdi mdi-cart menu-icon"></i></span>
                <span class="menu-title">Orders</span>
            </a>
        </li>
        <li class="nav-item {{ request()->route()->getName() === 'upwork.payments.get' ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('upwork.payments.get') }}">
                <span class="icon-bg"><i class="mdi mdi-currency-usd menu-icon"></i></span>
                <span class="menu-title">Payments</span>
            </a>
        </li>
    </ul>
</nav>
