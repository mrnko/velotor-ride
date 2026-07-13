@php
    $brand = config('velotor.brand');
    $seo = data_get($page ?? [], 'props.seo', []);

    $seoTitle = $seo['title'] ?? ($brand['full_name'].' — '.$brand['tagline']);
    $seoDescription = $seo['description'] ?? $brand['description'];
    $seoCanonical = $seo['canonical'] ?? url()->current();
    $seoImage = $seo['og_image'] ?? asset('images/og-image.jpg');
    $seoType = $seo['type'] ?? 'website';
    $seoRobots = $seo['robots'] ?? 'index, follow';
    $pageSchema = $seo['schema'] ?? null;

    $orgLd = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $brand['full_name'],
        'url' => $brand['site_url'],
        'logo' => rtrim($brand['site_url'], '/').'/images/logo.png',
        'description' => $brand['description'],
        'sameAs' => [$brand['telegram_url'], $brand['shop_url']],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
<!DOCTYPE html>
<html lang="uk">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="theme-color" content="#ffffff">
        <meta name="format-detection" content="telephone=no">

        <title inertia>{{ $seoTitle }}</title>
        <meta name="description" content="{{ $seoDescription }}">
        <meta name="author" content="{{ $brand['developer_name'] }}">
        <meta name="robots" content="{{ $seoRobots }}">
        <link rel="canonical" href="{{ $seoCanonical }}">

        {{-- Open Graph --}}
        <meta property="og:site_name" content="{{ $brand['full_name'] }}">
        <meta property="og:type" content="{{ $seoType }}">
        <meta property="og:url" content="{{ $seoCanonical }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:image" content="{{ $seoImage }}">
        <meta property="og:image:width" content="700">
        <meta property="og:image:height" content="300">
        <meta property="og:locale" content="uk_UA">

        {{-- Twitter card --}}
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        <meta name="twitter:description" content="{{ $seoDescription }}">
        <meta name="twitter:image" content="{{ $seoImage }}">

        {{-- Favicons (all devices) --}}
        <link rel="icon" href="/images/favicons/favicon.ico" sizes="any">
        <link rel="icon" type="image/png" sizes="16x16" href="/images/favicons/favicon-16x16.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/images/favicons/favicon-32x32.png">
        <link rel="icon" type="image/png" sizes="96x96" href="/images/favicons/favicon-96x96.png">
        <link rel="icon" type="image/png" sizes="192x192" href="/images/favicons/android-icon-192x192.png">
        <link rel="apple-touch-icon" href="/images/pwa/apple-touch-icon.png">
        <link rel="apple-touch-icon" sizes="180x180" href="/images/pwa/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">

        {{-- PWA / installable app --}}
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="{{ $brand['name'] }}">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=montserrat:300,400,500,600,700,800,900" rel="stylesheet" />

        {{-- Site-wide structured data --}}
        <script type="application/ld+json">{!! $orgLd !!}</script>
        @if ($pageSchema)
        <script type="application/ld+json">{!! json_encode($pageSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
