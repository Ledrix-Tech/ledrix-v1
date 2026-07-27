@extends('emails.layouts.ledrix')

@section('title', 'CRM Portal Access')

@section('content')
    <h2 class="email-heading">Welcome to your CRM portal</h2>

    <p>Hello <strong>{{ $client->name }}</strong>,</p>

    <p>
        Your CRM portal access has been created successfully.
        You can now log in to manage your orders, briefs, tickets, invoices, and communication.
    </p>

    <h3 class="email-subheading">Your login credentials</h3>
    <table role="presentation" class="email-info-table" width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td width="100"><strong>Email</strong></td>
            <td>{{ $client->email }}</td>
        </tr>
        <tr>
            <td><strong>Password</strong></td>
            <td>{{ $password }}</td>
        </tr>
    </table>

    <p style="text-align:center;">
        <a href="{{ $loginUrl }}" class="email-btn">Login to CRM Portal</a>
    </p>

    <p class="email-muted">
        If you need help or support, contact your assigned project manager anytime.
    </p>
@endsection
