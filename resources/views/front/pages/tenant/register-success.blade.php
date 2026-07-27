@extends('front.layout.layout')

@section('title', 'Verify Email')

@section('robots', 'noindex, nofollow')

@push('styles')
    @include('front.includes.auth-styles')
@endpush

@section('main-content')
    <div class="auth-page auth-page-centered">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-xl-5">
                    <div class="auth-card auth-card-success text-center">
                        <div class="auth-verify-icon success">
                            <i class="bi bi-envelope-check"></i>
                        </div>
                        <h2>Almost there — verify your email</h2>
                        @if (!empty($plan))
                            <p class="small text-primary fw-semibold mb-2">Plan: {{ $plan }}</p>
                        @endif
                        <p class="text-muted mb-4">
                            We've sent a verification link to
                            @if (!empty($email))
                                <strong>{{ $email }}</strong>
                            @else
                                your email address
                            @endif
                            . Click the link to activate your trial and access your workspace.
                        </p>

                        <div class="auth-trial-banner text-start mb-4">
                            <i class="bi bi-info-circle-fill"></i>
                            <div>
                                <strong>What happens next?</strong>
                                <p class="mb-0 mt-1">Verify email → Sign in automatically → Open your tenant dashboard → Launch CRM.</p>
                            </div>
                        </div>

                        @if (config('app.debug') && !empty($verifyUrl))
                            <div class="auth-debug-box text-start mb-4">
                                <p class="small fw-semibold text-warning mb-2"><i class="bi bi-bug"></i> Local dev — verify link</p>
                                <a href="{{ $verifyUrl }}" class="btn btn-sm btn-warning w-100">Open verification link</a>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('tenant.verify-email.resend') }}" class="auth-resend-box text-start">
                            @csrf
                            <p class="small fw-semibold mb-2">Didn't receive the email?</p>
                            <div class="row g-2">
                                <div class="col-sm-8">
                                    <input type="email" name="email" value="{{ $email ?? old('email') }}"
                                        class="form-control form-control-sm" placeholder="Your account email" required>
                                </div>
                                <div class="col-sm-4">
                                    <button type="submit" class="btn btn-sm btn-outline-primary w-100">Resend</button>
                                </div>
                            </div>
                            <p class="small text-muted mt-2 mb-0">Check spam or promotions folders before resending.</p>
                        </form>

                        <p class="auth-footer-link mb-0 mt-4">
                            <a href="{{ route('tenant.login') }}"><i class="bi bi-arrow-left me-1"></i> Back to sign in</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
