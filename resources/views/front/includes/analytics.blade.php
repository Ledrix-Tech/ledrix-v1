{{-- Optional Meta Pixel / Google Ads / GTM — set IDs in .env (then config:cache on deploy) --}}
@php
    $metaPixelId = trim((string) config('marketing.meta_pixel_id'));
    $googleAdsId = trim((string) config('marketing.google_ads_id'));
    $gtmId = trim((string) config('marketing.gtm_id'));
@endphp

@if ($metaPixelId !== '' || $gtmId !== '' || $googleAdsId !== '')
    <link rel="preconnect" href="https://connect.facebook.net" crossorigin>
    <link rel="dns-prefetch" href="https://www.facebook.com">
    <link rel="dns-prefetch" href="https://www.googletagmanager.com">
@endif

@if ($gtmId !== '')
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','{{ $gtmId }}');</script>
@endif

@if ($metaPixelId !== '')
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script',
        'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @json($metaPixelId));
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
        src="https://www.facebook.com/tr?id={{ urlencode($metaPixelId) }}&ev=PageView&noscript=1" alt=""></noscript>
@endif

@if ($googleAdsId !== '' && $gtmId === '')
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $googleAdsId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($googleAdsId));
    </script>
@endif
