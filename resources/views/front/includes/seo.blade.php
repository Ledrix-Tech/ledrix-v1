@php
    $siteName = config('seo.site_name', 'Ledrix CRM');
    $pageTitle = trim($__env->yieldContent('title'));
    $seoTitle = trim($__env->yieldContent('seo_title'));
    $fullTitle = $seoTitle !== '' ? $seoTitle : ($pageTitle !== '' ? $pageTitle : config('seo.default_title'));
    if ($seoTitle === '' && $pageTitle !== '' && ! str_contains(strtolower($pageTitle), 'ledrix')) {
        $fullTitle = $pageTitle . ' | ' . $siteName;
    }

    $description = trim($__env->yieldContent('meta_description')) ?: config('seo.default_description');
    $keywords = trim($__env->yieldContent('meta_keywords')) ?: config('seo.default_keywords');
    $robots = trim($__env->yieldContent('robots')) ?: 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
    $ogType = trim($__env->yieldContent('og_type')) ?: 'website';
    $isIndexable = ! str_contains(strtolower($robots), 'noindex');

    $canonicalOverride = trim($__env->yieldContent('canonical'));
    $canonical = $canonicalOverride !== '' ? $canonicalOverride : url()->current();

    $ogImagePath = trim($__env->yieldContent('og_image')) ?: config('seo.og_image');
    $ogImage = str_starts_with($ogImagePath, 'http') ? $ogImagePath : asset($ogImagePath);
    $ogImageAlt = trim($__env->yieldContent('og_image_alt')) ?: ($siteName . ' — multi-tenant sales CRM software');

    $org = config('seo.organization');
    $orgUrl = rtrim((string) (config('seo.site_url') ?: ($org['url'] ?? null) ?: config('app.url')), '/');
    // Never emit localhost into production schema / Meta domain signals
    if (app()->environment('production') && preg_match('#^https?://(127\.0\.0\.1|localhost)(:\d+)?#i', $orgUrl)) {
        $orgUrl = rtrim((string) (config('seo.site_url') ?: 'https://ledrix.co'), '/');
    }
    $orgLogo = asset($org['logo'] ?? config('seo.og_image'));

    $graph = [
        [
            '@type' => 'Organization',
            '@id' => $orgUrl . '#organization',
            'name' => $org['name'],
            'legalName' => $org['legal_name'] ?? $org['name'],
            'url' => $orgUrl,
            'logo' => $orgLogo,
            'email' => $org['email'] ?? null,
            'foundingDate' => $org['founding_date'] ?? null,
            'sameAs' => $org['same_as'] ?? [],
            'founder' => [
                '@type' => 'Person',
                'name' => config('seo.founder.name'),
                'url' => config('seo.founder.linkedin'),
                'jobTitle' => config('seo.founder.job_title'),
            ],
        ],
        [
            '@type' => 'WebSite',
            '@id' => $orgUrl . '#website',
            'url' => $orgUrl,
            'name' => $siteName,
            'description' => config('seo.default_description'),
            'publisher' => ['@id' => $orgUrl . '#organization'],
            'inLanguage' => 'en-US',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => $orgUrl . '/faq?q={search_term_string}',
                'query-input' => 'required name=search_term_string',
            ],
        ],
    ];

    if ($isIndexable) {
        $graph[] = [
            '@type' => 'SoftwareApplication',
            'name' => 'Ledrix CRM',
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web',
            'url' => $orgUrl,
            'description' => config('seo.default_description'),
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
                'description' => 'Free trial available',
            ],
            'publisher' => ['@id' => $orgUrl . '#organization'],
        ];
    }
@endphp

@if (config('seo.facebook_domain_verification'))
    <meta name="facebook-domain-verification" content="{{ config('seo.facebook_domain_verification') }}">
@endif
@if (config('seo.google_site_verification'))
    <meta name="google-site-verification" content="{{ config('seo.google_site_verification') }}">
@endif

<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ $description }}">
<meta name="keywords" content="{{ $keywords }}">
<meta name="author" content="{{ config('seo.founder.name') }}, {{ $siteName }}">
<meta name="robots" content="{{ $robots }}">
<meta name="theme-color" content="{{ config('seo.theme_color', '#4338ca') }}">
<meta name="application-name" content="{{ $siteName }}">
<meta name="format-detection" content="telephone=no">
<link rel="canonical" href="{{ $canonical }}">
<link rel="alternate" hreflang="en" href="{{ $canonical }}">
<link rel="alternate" hreflang="x-default" href="{{ $canonical }}">

<meta property="og:locale" content="en_US">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:alt" content="{{ $ogImageAlt }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:image" content="{{ $ogImage }}">
@if (config('seo.twitter_handle'))
    <meta name="twitter:site" content="{{ config('seo.twitter_handle') }}">
@endif

<link rel="me" href="{{ config('seo.founder.linkedin') }}">

<script type="application/ld+json">
{!! json_encode([
    '@'.'context' => 'https://schema.org',
    '@'.'graph' => $graph,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
</script>

@stack('schema')
