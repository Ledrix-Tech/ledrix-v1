@php $isAdminOrg = ($organizationPortal ?? 'tenant') === 'admin'; @endphp
@extends($isAdminOrg ? 'admin.layout.layout' : 'front.layout.layout')

@section('title', $isAdminOrg ? 'Organization | Ticket #'.$ticket->id : 'Ticket #'.$ticket->id)
@section('robots', 'noindex, nofollow')

@section('admin-content')
    @include('front.pages.tenant.partials.support-show-body')
@endsection

@section('main-content')
    @include('front.pages.tenant.partials.support-show-body')
@endsection
