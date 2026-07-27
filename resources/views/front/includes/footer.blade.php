<!-- FOOTER -->
<footer class="footer text-white py-4">
    <div class="container">
        <div class="row align-items-start g-4">
            <div class="col-lg-4">
                <a class="navbar-brand-footer d-inline-block mb-2" href="{{ route('index.get') }}">
                    <img src="{{ asset(config('seo.front_logo', 'front-assets/imgs/logo-ic.png')) }}" width="120" height="34"
                        alt="Ledrix CRM logo — sales CRM for agencies and closers">
                </a>
                @include('front.includes.social-icons')
                <p class="small mb-0 opacity-75">
                    Multi-tenant sales CRM for agencies and closers — leads, sellers, orders, and payments in one workspace.
                </p>
            </div>
            <div class="col-lg-4">
                <strong class="d-block mb-2 small text-uppercase opacity-75">Product</strong>
                <a href="{{ route('features.get') }}" class="text-white d-block mb-1 text-decoration-none">Features</a>
                <a href="{{ route('pricing.get') }}" class="text-white d-block mb-1 text-decoration-none">Pricing</a>
                <a href="{{ route('about.get') }}" class="text-white d-block mb-1 text-decoration-none">About</a>
                <a href="{{ route('faq.get') }}" class="text-white d-block mb-1 text-decoration-none">FAQ</a>
                <a href="{{ route('contact-us.get') }}" class="text-white d-block text-decoration-none">Contact</a>
            </div>
            <div class="col-lg-4">
                <strong class="d-block mb-2 small text-uppercase opacity-75">Company</strong>
                <a href="{{ route('about.get') }}#founder" class="text-white d-block mb-1 text-decoration-none">{{ config('seo.founder.name') }} — Founder</a>
                <a href="javascript:void(0);" class="text-white d-block mb-1 text-decoration-none">Sitemap</a>
                <span class="d-block mt-3 small opacity-75">&copy; {{ date('Y') }} Ledrix CRM. All rights reserved.</span>
            </div>
        </div>
    </div>
</footer>
