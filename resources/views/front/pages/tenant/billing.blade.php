@php $isAdminOrg = ($organizationPortal ?? 'tenant') === 'admin'; @endphp
@extends($isAdminOrg ? 'admin.layout.layout' : 'front.layout.layout')

@section('title', $isAdminOrg ? 'Organization | Billing' : 'Billing')
@section('robots', 'noindex, nofollow')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-assets/css/tenant-billing.css') }}">
@endpush

@section('admin-content')
    @include('front.pages.tenant.partials.billing-body')
@endsection

@section('main-content')
    @include('front.pages.tenant.partials.billing-body')
@endsection
