@php
    $rows = is_array($attribution ?? null) ? $attribution : [];
    $sourceLabel = $source ?? null;
    $landing = $landingPath ?? null;
@endphp

@if ($sourceLabel || $landing || $rows !== [])
    <div class="{{ $wrapClass ?? '' }}">
        @if ($sourceLabel)
            <p class="mb-2">
                <strong>Source:</strong>
                <span class="badge bg-info text-dark">{{ str_replace('_', ' ', $sourceLabel) }}</span>
            </p>
        @endif
        @if ($landing)
            <p class="mb-2"><strong>Landing:</strong> <code>{{ $landing }}</code></p>
        @endif
        @if ($rows !== [])
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <tbody>
                        @foreach ($rows as $key => $value)
                            @if ($key === 'source')
                                @continue
                            @endif
                            <tr>
                                <th class="text-muted fw-normal" style="width: 38%; white-space: nowrap;">
                                    {{ \App\Support\MarketingAttribution::fieldLabel((string) $key) }}
                                </th>
                                <td><code class="small text-break">{{ $value }}</code></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@else
    <p class="text-muted mb-0">{{ $empty ?? 'No marketing attribution recorded.' }}</p>
@endif
