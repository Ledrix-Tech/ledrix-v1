@extends('emails.layouts.ledrix', ['contentAlign' => 'center'])

@section('title', 'Access Restricted')

@section('content')
    <h2 class="email-heading" style="color:#db165b;">Access restricted</h2>

    <p>You cannot use the CRM outside office premises.</p>

    <p class="email-muted">If you believe this is an error, contact your administrator.</p>
@endsection

@section('signoff')
    <p style="margin:16px 0 0;font-size:15px;color:#555;font-style:italic;">
        <strong style="color:#673187;font-style:normal;">The Ledrix Team</strong>
    </p>
@endsection
