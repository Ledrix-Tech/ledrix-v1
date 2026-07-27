@php
    use App\Support\BriefServiceCatalog;
    use Illuminate\Support\Str;

    $meta = is_array($briefMeta ?? null) ? $briefMeta : [];
    $query = isset($meta['query']) && is_array($meta['query']) ? $meta['query'] : [];
    $attachments = $meta['attachments'] ?? [];
    $hasAnswers = collect($query)->filter(fn ($v) => filled($v))->isNotEmpty();
@endphp

@if (! $hasAnswers)
    <div class="alert alert-info mb-0">
        <i class="bi bi-info-circle me-1"></i>
        The client has not submitted project information for this order yet.
    </div>
@else
    <div class="brief-readonly-display">
        @foreach ($query as $field => $value)
            @continue(! filled($value))
            <div class="brief-readonly-row">
                <div class="brief-readonly-label">{{ Str::headline(str_replace('_', ' ', $field)) }}</div>
                <div class="brief-readonly-value">
                    @if (is_array($value))
                        {{ implode(', ', array_map('strval', $value)) }}
                    @else
                        {!! nl2br(e((string) $value)) !!}
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    @if (! empty($attachments))
        <div class="mt-4">
            <strong class="d-block mb-2">Attachments</strong>
            <ul class="mb-0 ps-3">
                @foreach ($attachments as $file)
                    <li>
                        <a href="{{ BriefServiceCatalog::attachmentUrl($file) }}" target="_blank" rel="noopener">
                            {{ basename($file) }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endif
