{{-- Fire once on thank-you / register-success pages (requires analytics.blade.php in layout head) --}}
@php
    $event = $event ?? 'Lead'; // Lead | CompleteRegistration | StartTrial
    $metaPixelId = trim((string) config('marketing.meta_pixel_id'));
    $googleAdsId = trim((string) config('marketing.google_ads_id'));
    $trialLabel = config('marketing.google_ads_trial_conversion_label');
    $leadLabel = config('marketing.google_ads_lead_conversion_label');
    $conversionLabel = in_array($event, ['CompleteRegistration', 'StartTrial'], true) ? $trialLabel : $leadLabel;
    $fbEvent = $event === 'StartTrial' ? 'StartTrial' : ($event === 'CompleteRegistration' ? 'CompleteRegistration' : 'Lead');
@endphp

@if ($metaPixelId !== '')
<script>
    (function () {
        var eventName = @json($fbEvent);
        var fire = function () {
            if (typeof fbq === 'function') {
                fbq('track', eventName);
                return true;
            }
            return false;
        };
        if (!fire()) {
            // Pixel stub/script may still be loading
            var tries = 0;
            var timer = setInterval(function () {
                tries += 1;
                if (fire() || tries > 40) {
                    clearInterval(timer);
                }
            }, 100);
        }
    })();
</script>
@endif

@if ($googleAdsId !== '' && $conversionLabel)
<script>
    if (typeof gtag === 'function') {
        gtag('event', 'conversion', {
            'send_to': @json($googleAdsId . '/' . $conversionLabel)
        });
    }
</script>
@endif

@if (trim((string) config('marketing.gtm_id')) !== '')
<script>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        event: @json($event === 'Lead' ? 'generate_lead' : 'sign_up'),
        marketing_event: @json($event)
    });
</script>
@endif
