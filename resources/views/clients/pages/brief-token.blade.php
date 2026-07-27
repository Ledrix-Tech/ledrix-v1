@extends('clients.layouts.layout')

@section('title', 'Brief Form | Client Portal')

@section('client-content')
    @php
        $viewKey = \App\Support\BriefServiceCatalog::viewKeyFor($order->service_name);
    @endphp

    <div class="crm-page-header">
        <div>
            <h1>Project brief</h1>
            <p>{{ $order->service_name }} · Invoice #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</p>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-body">
            @includeIf("clients.pages.questionnaires.$viewKey", [
                'order' => $order,
                'brief' => $brief,
                'questionnair' => $questionnair,
                'mode' => $mode ?? 'dashboard',
                'token' => $token ?? null,
            ])
        </div>
    </div>
@endsection
