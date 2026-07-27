@extends('front.layout.layout')

@section('title', 'Email Verification')

@section('robots', 'noindex, nofollow')

@push('styles')
    @include('front.includes.auth-styles')
@endpush

@section('main-content')
    <div class="auth-page auth-page-centered">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-xl-5">
                    <div class="auth-card text-center">
                        @if ($success ?? false)
                            <div class="auth-verify-icon success">
                                <i class="bi bi-check-lg"></i>
                            </div>
                            <h2>Email verified!</h2>
                            <p class="text-muted mb-4">
                                Your workspace is active. Redirecting you to your dashboard…
                            </p>
                            <a href="{{ route('tenant.dashboard') }}" class="btn btn-primary auth-btn-primary">
                                Go to dashboard
                            </a>
                        @else
                            <div class="auth-verify-icon error">
                                <i class="bi bi-x-lg"></i>
                            </div>
                            <h2>Verification failed</h2>
                            <p class="text-muted mb-4">
                                {{ $message ?? 'This link is invalid or has expired. Request a new verification email below.' }}
                            </p>

                            <form method="POST" action="{{ route('tenant.verify-email.resend') }}" class="auth-resend-box text-start">
                                @csrf
                                <div class="row g-2">
                                    <div class="col-sm-8">
                                        <input type="email" name="email" value="{{ old('email') }}"
                                            class="form-control form-control-sm" placeholder="Your account email" required>
                                    </div>
                                    <div class="col-sm-4">
                                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">Resend link</button>
                                    </div>
                                </div>
                            </form>

                            <p class="auth-footer-link mb-0 mt-4">
                                <a href="{{ route('tenant.login') }}">Return to sign in</a>
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
