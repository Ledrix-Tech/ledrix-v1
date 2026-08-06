@extends('central.layout.auth')

@section('title', 'Super Admin | Accept Invite')

@section('auth-content')
    <div class="sa-auth-page">
        <div class="sa-auth-card">
            <img src="{{ asset(config('branding.logo')) }}" alt="Ledrix" class="sa-auth-logo">
            <h1>Accept invite</h1>
            <p class="sa-auth-sub">
                Create your {{ $invite['role'] }} account for {{ $invite['email'] }}
            </p>

            @if ($errors->any())
                <div class="alert alert-danger py-2">
                    <ul class="mb-0 small">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form action="{{ route('super-admin.invite.accept.post', $token) }}" method="post">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required autofocus
                        placeholder="Min 8 chars, upper, lower, number, symbol">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirmation">Confirm password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-submit">Create account</button>
            </form>
        </div>
    </div>
@endsection
