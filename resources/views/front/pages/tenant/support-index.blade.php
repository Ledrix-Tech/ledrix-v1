@php $isAdminOrg = ($organizationPortal ?? 'tenant') === 'admin'; @endphp
@extends($isAdminOrg ? 'admin.layout.layout' : 'front.layout.layout')

@section('title', $isAdminOrg ? 'Organization | Support' : 'Support')
@section('robots', 'noindex, nofollow')

@section('admin-content')
    @include('front.pages.tenant.partials.support-index-body')
@endsection

@section('main-content')
    @include('front.pages.tenant.partials.support-index-body')
@endsection
