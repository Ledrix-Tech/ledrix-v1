@php
    $data = is_array($prediction ?? null)
        ? $prediction
        : (is_string($prediction ?? null) ? json_decode($prediction, true) : null);
    $compact = $compact ?? false;
@endphp

@if (empty($data))
    <span class="crm-status crm-status-neutral">—</span>
@elseif (($data['status'] ?? null) === 'fake')
    <span class="crm-status crm-status-danger">Fake</span>
@else
    @php
        $strength = strtolower((string) ($data['strength'] ?? 'cold'));
        $strengthClass = match ($strength) {
            'hot' => 'crm-status-danger',
            'warm' => 'crm-status-warning',
            default => 'crm-status-info',
        };
    @endphp
    <span class="crm-status {{ $strengthClass }}">{{ ucfirst($strength) }}</span>
    @if (isset($data['score']) && $data['score'] !== null)
        <div class="small text-muted">{{ (int) $data['score'] }}/100</div>
    @endif
    @unless ($compact)
        @if (! empty($data['category']))
            <div class="small text-muted text-capitalize">{{ str_replace('_', ' ', $data['category']) }}</div>
        @endif
        @if (! empty($data['sentiment']))
            <div class="small text-muted">{{ ucfirst($data['sentiment']) }} sentiment</div>
        @endif
    @endunless
@endif
