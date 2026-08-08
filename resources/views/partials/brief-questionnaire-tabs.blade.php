@php

    use App\Support\BriefServiceCatalog;



    $mode = $mode ?? 'client';

    $isSellerView = $mode === 'seller';

    $tabPrefix = $tabPrefix ?? 'brief-tab';

    $filteredOrders = $filteredOrders ?? collect();

    $activeOrderId = request()->filled('order') ? (int) request('order') : null;

    $activeIndex = 0;



    if ($activeOrderId) {

        $found = $filteredOrders->search(fn ($o) => (int) $o->id === $activeOrderId);

        $activeIndex = $found === false ? 0 : (int) $found;

    }

@endphp



@if ($filteredOrders->isEmpty())

    <div class="text-center text-muted py-5">

        <i class="bi bi-journal-x d-block fs-1 mb-2 text-secondary"></i>

        No project briefs available yet.

    </div>

@else

    <nav>

        <div class="nav nav-tabs" id="{{ $tabPrefix }}-nav" role="tablist">

            @foreach ($filteredOrders as $index => $order)

                <button class="nav-link mx-1 {{ $index === $activeIndex ? 'active' : '' }}"

                    id="{{ $tabPrefix }}-btn-{{ $index }}" data-bs-toggle="tab"

                    data-bs-target="#{{ $tabPrefix }}-pane-{{ $index }}" type="button" role="tab"

                    aria-controls="{{ $tabPrefix }}-pane-{{ $index }}"

                    aria-selected="{{ $index === $activeIndex ? 'true' : 'false' }}">

                    {{ $order->service_name }} INV#000{{ $order->id }}

                </button>

            @endforeach

        </div>

    </nav>

    <div class="tab-content pt-4" id="{{ $tabPrefix }}-content">

        @foreach ($filteredOrders as $index => $order)

            <div class="tab-pane fade {{ $index === $activeIndex ? 'show active' : '' }}"

                id="{{ $tabPrefix }}-pane-{{ $index }}" role="tabpanel"

                aria-labelledby="{{ $tabPrefix }}-btn-{{ $index }}" tabindex="0">

                @if ($isSellerView)

                    @include('partials.brief-seller-order-panel', ['order' => $order])

                @else

                    @php

                        $viewKey = BriefServiceCatalog::viewKeyFor($order->service_name);

                        $isCompleted = ($order->brief?->status ?? null) === 'completed';

                    @endphp

                    @if ($isCompleted)

                        <div class="alert alert-success d-flex align-items-center gap-2 mb-4">

                            <i class="bi bi-lock-fill"></i>

                            <span>This brief is completed and locked. Contact your seller if changes are needed.</span>

                        </div>

                        @include('partials.brief-readonly-display', [

                            'briefMeta' => BriefServiceCatalog::metaForView($order->brief),

                        ])

                    @elseif ($viewKey)

                        @includeIf('clients.pages.questionnaires.' . $viewKey, [

                            'order' => $order,

                            'brief' => BriefServiceCatalog::metaForView($order->brief),

                            'questionnair' => $order->brief,

                            'mode' => $mode === 'token' ? 'token' : 'dashboard',

                            'token' => $token ?? null,

                        ])

                    @else

                        <div class="alert alert-warning mb-0">

                            No questionnaire is available for this service.

                        </div>

                    @endif

                @endif

            </div>

        @endforeach

    </div>

@endif

