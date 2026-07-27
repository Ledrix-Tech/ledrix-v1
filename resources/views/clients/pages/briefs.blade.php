@extends('clients.layouts.layout')

@section('title', 'Briefs | Client Portal')

@section('client-content')
    @php
        use App\Support\BriefServiceCatalog;

        $filteredOrders = BriefServiceCatalog::filterOrdersForBriefs($orders);
    @endphp

    <div class="crm-page-header">
        <div>
            <h1>Project briefs</h1>
            <p>Submit your project information for each order so our team can get started.</p>
        </div>
    </div>

    <div class="crm-card">
        <div class="crm-card-body">
            @if ($filteredOrders->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-journal-x d-block fs-1 mb-2 text-secondary"></i>
                    No project briefs are available for your orders yet.
                </div>
            @else
                @include('partials.brief-questionnaire-tabs', [
                    'filteredOrders' => $filteredOrders,
                    'mode' => 'client',
                    'tabPrefix' => 'client-brief',
                ])
            @endif
        </div>
    </div>
@endsection
