@php $isAdminOrg = ($organizationPortal ?? 'tenant') === 'admin'; @endphp
@extends($isAdminOrg ? 'admin.layout.layout' : 'front.layout.layout')

@section('title', $isAdminOrg ? 'Organization | API tokens' : 'API tokens')
@section('robots', 'noindex, nofollow')

@section('admin-content')
    @include('front.pages.tenant.partials.api-tokens-body')
@endsection

@section('main-content')
    @include('front.pages.tenant.partials.api-tokens-body')
@endsection
