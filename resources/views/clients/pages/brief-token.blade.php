@extends('clients.layout.auth')

@section('title', 'Project Brief | Ledrix')

@section('auth-content')
    @php
        use App\Support\BriefServiceCatalog;

        $viewKey = BriefServiceCatalog::viewKeyFor($order->service_name);
        $isCompleted = ($questionnair->status ?? null) === 'completed';
    @endphp

    <div class="container py-5" style="max-width: 960px;">
        <div class="text-center mb-4">
            <img src="{{ asset(config('branding.logo')) }}" alt="Ledrix" style="height: 40px;">
            <h1 class="h3 mt-3 mb-1">Project brief</h1>
            <p class="text-muted mb-0">{{ $order->service_name }} · Invoice #{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</p>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                @if ($isCompleted)
                    <div class="alert alert-success d-flex align-items-center gap-2 mb-4">
                        <i class="bi bi-lock-fill"></i>
                        <span>This brief is completed and locked.</span>
                    </div>
                    @include('partials.brief-readonly-display', [
                        'briefMeta' => $brief,
                    ])
                @elseif ($viewKey)
                    @includeIf("clients.pages.questionnaires.$viewKey", [
                        'order' => $order,
                        'brief' => $brief,
                        'questionnair' => $questionnair,
                        'mode' => $mode ?? 'token',
                        'token' => $token ?? null,
                    ])
                @else
                    <div class="alert alert-warning mb-0">
                        No questionnaire is available for this service.
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
