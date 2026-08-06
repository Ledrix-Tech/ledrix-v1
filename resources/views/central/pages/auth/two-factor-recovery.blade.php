@extends('central.layout.auth')

@section('title', '2FA recovery codes')

@section('auth-content')
    <div class="card shadow-sm mx-auto" style="max-width: 480px;">
        <div class="card-body p-4">
            <h1 class="h4 mb-2">Save your recovery codes</h1>
            <p class="text-muted small">Store these somewhere safe. Each code works once if you lose your authenticator.</p>
            <ul class="list-group mb-3 font-monospace">
                @foreach ($codes as $code)
                    <li class="list-group-item">{{ $code }}</li>
                @endforeach
            </ul>
            <a href="{{ route('super-admin.index.get') }}" class="btn btn-primary w-100">Done</a>
        </div>
    </div>
@endsection
