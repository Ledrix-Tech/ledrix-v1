@extends('front.layout.layout')

@section('title', 'Create Account | Ledrix')

@push('styles')
    @include('front.includes.auth-styles')
@endpush

@section('main-content')
    <div class="auth-page auth-page-centered">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7 col-xl-6">
                    <div class="auth-card text-center">
                        <div class="auth-verify-icon success mx-auto">
                            <i class="bi bi-rocket-takeoff"></i>
                        </div>
                        <h2 class="mb-2">Start with a plan</h2>
                        <p class="text-muted mb-4">
                            Ledrix registration is plan-based. Pick a package to begin your free trial — no credit card required.
                        </p>
                        <a href="{{ route('pricing.get') }}" class="btn btn-primary auth-btn-primary w-100 mb-3">
                            View pricing & start trial
                        </a>
                        <p class="auth-footer-link mb-0">
                            Already have an account? <a href="{{ route('tenant.login') }}">Sign in</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
