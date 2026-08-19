@php $isAdminOrg = ($organizationPortal ?? 'tenant') === 'admin'; @endphp
@extends($isAdminOrg ? 'admin.layout.layout' : 'front.layout.layout')

@section('title', $isAdminOrg ? 'Organization | Data export' : 'Workspace data export')
@section('robots', 'noindex, nofollow')

@section('admin-content')
    @include('front.pages.tenant.partials.data-export-body')
@endsection

@section('main-content')
    @include('front.pages.tenant.partials.data-export-body')
@endsection
