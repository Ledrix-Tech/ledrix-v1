@extends('admin.layout.auth')

@section('title', '2FA recovery codes')

@section('auth-content')
    <div class="crm-auth-page">
        <div class="crm-auth-card" style="max-width: 480px;">
            <h1>Save your recovery codes</h1>
            <p class="crm-auth-sub">Store these somewhere safe. Each code works once if you lose your authenticator.</p>
            <ul class="list-group mb-3 font-monospace text-start">
                @foreach ($codes as $code)
                    <li class="list-group-item">{{ $code }}</li>
                @endforeach
            </ul>
            <a href="{{ route('admin.index.get') }}" class="btn btn-submit">Done</a>
        </div>
    </div>
@endsection
