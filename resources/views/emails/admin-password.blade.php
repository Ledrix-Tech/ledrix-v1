@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Password Reset')

@section('content')
    <h1 class="email-heading" style="text-transform:uppercase;font-size:28px;">Forgot your password?</h1>

    <p>
        We cannot send your existing password, but we can help you create a new one.
        Click the button below to safely reset your password.
    </p>

    <a href="{{ route('admin.reset.get', $token) }}" class="email-btn">Reset Password</a>
@endsection

@section('signoff')
    <p style="margin:16px 0 0;font-size:15px;color:#555;font-style:italic;">
        Stay secure,<br>
        <strong style="color:#673187;font-style:normal;">The Ledrix Team</strong>
    </p>
@endsection
