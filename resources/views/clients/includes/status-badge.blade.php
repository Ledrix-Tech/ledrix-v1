@php
    $class = match ($status) {
        'paid', 'completed', 'success' => 'crm-status-success',
        'pending', 'open' => 'crm-status-warning',
        'canceled', 'cancelled' => 'crm-status-danger',
        default => 'crm-status-neutral',
    };
@endphp
<span class="crm-status {{ $class }}">{{ ucfirst($status) }}</span>
