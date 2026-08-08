{{-- Fire once on thank-you / register-success pages --}}
@php
    $event = $event ?? 'Lead'; // Lead | CompleteRegistration | StartTrial
    $metaPixelId = config('marketing.meta_pixel_id');
    $googleAdsId = config('marketing.google_ads_id');
    $trialLabel = config('marketing.google_ads_trial_conversion_label');
    $leadLabel = config('marketing.google_ads_lead_conversion_label');
    $conversionLabel = in_array($event, ['CompleteRegistration', 'StartTrial'], true) ? $trialLabel : $leadLabel;
@endphp

@if ($metaPixelId)
<script>
    if (typeof fbq === 'function') {
        fbq('track', @json($event === 'StartTrial' ? 'StartTrial' : ($event === 'CompleteRegistration' ? 'CompleteRegistration' : 'Lead')));
    }
</script>
@endif

@if ($googleAdsId && $conversionLabel)
<script>
    if (typeof gtag === 'function') {
        gtag('event', 'conversion', {
            'send_to': @json($googleAdsId . '/' . $conversionLabel)
        });
    }
</script>
@endif

@if (config('marketing.gtm_id'))
<script>
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        event: @json($event === 'Lead' ? 'generate_lead' : 'sign_up'),
        marketing_event: @json($event)
    });
</script>
@endif
