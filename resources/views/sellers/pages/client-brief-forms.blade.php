@extends('sellers.layout.layout')

@section('title', 'Seller | Client Briefs')

@section('sellers-content')
    <div class="crm-page-header">
        <div>
            <h1>{{ $client->name }}’s project briefs</h1>
            <p>Review client-submitted project info and update brief status. Clients fill briefs in their portal.</p>
        </div>
        <div class="crm-page-actions">
            <a href="{{ route('seller.briefs.get') }}" class="btn btn-sm btn-crm-outline">
                <i class="bi bi-journal-text me-1"></i> All briefs
            </a>
        </div>
    </div>

    @php
        use App\Support\BriefServiceCatalog;

        $filteredOrders = BriefServiceCatalog::filterOrdersForBriefs($orders);
    @endphp

    <div class="crm-card">
        <div class="crm-card-body">
            @include('partials.brief-questionnaire-tabs', [
                'filteredOrders' => $filteredOrders,
                'mode' => 'seller',
                'tabPrefix' => 'seller-client-brief',
            ])
        </div>
    </div>

    <script>
        document.querySelectorAll('.js-copy-link').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var url = btn.getAttribute('data-url');
                if (!url || !navigator.clipboard) {
                    return;
                }

                navigator.clipboard.writeText(url).then(function () {
                    var original = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check2 me-1"></i> Copied';
                    setTimeout(function () {
                        btn.innerHTML = original;
                    }, 2000);
                });
            });
        });
    </script>
@endsection
