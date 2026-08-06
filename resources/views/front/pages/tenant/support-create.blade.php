@php $isAdminOrg = ($organizationPortal ?? 'tenant') === 'admin'; @endphp
@extends($isAdminOrg ? 'admin.layout.layout' : 'front.layout.layout')

@section('title', $isAdminOrg ? 'Organization | New ticket' : 'New Support Ticket')
@section('robots', 'noindex, nofollow')

@section('admin-content')
    @include('front.pages.tenant.partials.support-create-body')
@endsection

@section('main-content')
    @include('front.pages.tenant.partials.support-create-body')
@endsection
