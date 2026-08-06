@php $isAdminOrg = ($organizationPortal ?? 'tenant') === 'admin'; @endphp
@extends($isAdminOrg ? 'admin.layout.layout' : 'front.layout.layout')

@section('title', $isAdminOrg ? 'Organization | Invoice' : 'Invoice '.$invoice->invoice_number)
@section('robots', 'noindex, nofollow')

@push('styles')
    <style>
        @media print {
            .crm-sidebar, .crm-sidebar-overlay, .crm-topbar, header, .btn, a.text-muted { display: none !important; }
            .crm-main, main { padding: 0 !important; margin: 0 !important; }
        }
    </style>
@endpush

@section('admin-content')
    @include('front.pages.tenant.partials.invoice-show-body')
@endsection

@section('main-content')
    @include('front.pages.tenant.partials.invoice-show-body')
@endsection
