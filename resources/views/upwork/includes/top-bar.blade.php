<style>
    .navbar .navbar-brand-wrapper .navbar-brand img {
        width: calc(258px - 120px);
        max-width: 100% !important;
        height: auto;
        margin: auto;
        vertical-align: middle;
    }

    .navbar .navbar-brand-wrapper .navbar-brand.brand-logo-mini img {
        width: calc(70px - 50px);
        width: 80%;
        height: auto;
        margin: auto;
    }
</style>
<nav class="navbar default-layout-navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
        <a class="navbar-brand brand-logo" href="{{ route('admin.index.get') }}">
            <img src="{{ asset(config('branding.logo')) }}" alt="">
        </a>
        <a class="navbar-brand brand-logo-mini" href="">
            <img src="{{ asset(config('branding.logo')) }}" alt="">
        </a>
    </div>

    <div class="navbar-menu-wrapper d-flex align-items-stretch">
        <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
            <span class="mdi mdi-menu text-white" style="font-size:22px;"></span>
        </button>
        <ul class="navbar-nav navbar-nav-right">
            <li class="nav-item nav-profile dropdown">
                <a class="nav-link dropdown-toggle" id="profileDropdown" href="#" data-toggle="dropdown"
                    aria-expanded="false">
                    <div class="nav-profile-text">
                        @php
                            $user = Auth::guard('admin')->user() ?? 'Upwork Admin';
                        @endphp
                        <p class="mb-1 text-white">{{ ucfirst($user->name ?? 'Guest') }}</p>
                    </div>
                </a>
                <div class="dropdown-menu navbar-dropdown dropdown-menu-right p-0 border-0 font-size-sm"
                    aria-labelledby="profileDropdown" data-x-placement="bottom-end">
                    <div class="p-2 text-white proInfo">
                        <a class="dropdown-item py-1 d-flex align-items-center justify-content-between text-white"
                            href="{{ route('upwork.logout') }}">
                            <span>Log Out</span>
                            <i class="mdi mdi-logout ml-1"></i>
                        </a>
                    </div>
                </div>
            </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button"
            data-toggle="offcanvas">
            <span class="mdi mdi-menu"></span>
        </button>
    </div>
</nav>
