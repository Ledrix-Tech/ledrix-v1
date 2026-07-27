@php
    $socialLinks = [
        'facebook' => [
            'url' => config('seo.social.facebook'),
            'icon' => 'bi-facebook',
            'label' => 'Ledrix on Facebook',
        ],
        'instagram' => [
            'url' => config('seo.social.instagram'),
            'icon' => 'bi-instagram',
            'label' => 'Ledrix on Instagram',
        ],
        'linkedin' => [
            'url' => config('seo.social.linkedin'),
            'icon' => 'bi-linkedin',
            'label' => 'Ledrix on LinkedIn',
        ],
    ];
@endphp

@if (collect($socialLinks)->pluck('url')->filter()->isNotEmpty())
    <div class="mkt-footer-social" aria-label="Ledrix social media">
        @foreach ($socialLinks as $network => $social)
            @if (! empty($social['url']))
                <a href="{{ $social['url'] }}"
                    class="mkt-footer-social-link mkt-footer-social-link--{{ $network }}"
                    target="_blank"
                    rel="noopener noreferrer me"
                    aria-label="{{ $social['label'] }}">
                    <i class="bi {{ $social['icon'] }}"></i>
                </a>
            @endif
        @endforeach
    </div>
@endif
