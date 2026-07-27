@extends('clients.layouts.layout')

@section('title', 'Messages | Client Portal')

@section('client-content')
    <div class="crm-page-header">
        <div>
            <h1>Messages</h1>
            <p>Direct messaging with your project team.</p>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-body client-coming-soon-panel">
            <i class="bi bi-chat-dots"></i>
            <h2 class="h4 fw-bold mb-2">Messaging is coming soon</h2>
            <p class="text-muted mb-4 mx-auto" style="max-width: 480px">
                We're building a secure inbox so you can chat with your seller directly from the portal.
                For now, please use support tickets or contact your project manager.
            </p>
            <a href="{{ route('client.raised-tickets.get') }}" class="btn btn-crm-primary">
                <i class="bi bi-life-preserver me-1"></i> Go to tickets
            </a>
        </div>
    </div>
@endsection
