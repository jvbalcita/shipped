<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Shipped is intentionally paper-light. Set the substrate immediately to avoid a flash. --}}
        <style>
            html {
                background-color: #f4f4f0;
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])

        @if (isset($page['props']['seo']))
            @php($seo = $page['props']['seo'])
            <meta name="description" content="{{ $seo['description'] }}" />
            <meta name="robots" content="{{ $seo['robots'] }}" />
            <link rel="canonical" href="{{ $seo['canonical'] }}" />
            <meta property="og:title" content="{{ $seo['ogTitle'] }}" />
            <meta property="og:description" content="{{ $seo['ogDescription'] }}" />
            <meta property="og:type" content="{{ $seo['ogType'] }}" />
            <meta property="og:url" content="{{ $seo['ogUrl'] }}" />
            <meta property="og:site_name" content="Shipped" />
            <meta name="twitter:title" content="{{ $seo['twitterTitle'] }}" />
            <meta name="twitter:description" content="{{ $seo['twitterDescription'] }}" />
            <meta name="twitter:card" content="{{ $seo['twitterCard'] }}" />
            @if (filled($seo['image']))
                <meta property="og:image" content="{{ $seo['image'] }}" />
                <meta property="og:image:alt" content="{{ $seo['imageAlt'] }}" />
                <meta property="og:image:type" content="{{ $seo['imageType'] }}" />
                <meta property="og:image:width" content="{{ $seo['imageWidth'] }}" />
                <meta property="og:image:height" content="{{ $seo['imageHeight'] }}" />
                <meta name="twitter:image" content="{{ $seo['image'] }}" />
            @endif
            @foreach ($seo['jsonLd'] ?? [] as $schema)
                <script type="application/ld+json">@json($schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
            @endforeach
        @else
            @isset($page['props']['ogTitle'])
                <meta property="og:title" content="{{ $page['props']['ogTitle'] }}" />
                <meta name="twitter:title" content="{{ $page['props']['ogTitle'] }}" />
                <meta property="og:type" content="website" />
                <meta property="og:site_name" content="Shipped" />
            @endisset
            @isset($page['props']['ogDescription'])
                <meta name="description" content="{{ $page['props']['ogDescription'] }}" />
                <meta property="og:description" content="{{ $page['props']['ogDescription'] }}" />
            @endisset
            @isset($page['props']['ogImage'])
                <meta property="og:image" content="{{ $page['props']['ogImage'] }}" />
                <meta property="og:image:type" content="image/svg+xml" />
                <meta property="og:image:width" content="1200" />
                <meta property="og:image:height" content="630" />
                <meta name="twitter:card" content="summary_large_image" />
                <meta name="twitter:image" content="{{ $page['props']['ogImage'] }}" />
            @endisset
        @endif

        <x-inertia::head>
            <title>{{ $page['props']['seo']['title'] ?? config('app.name', 'Shipped') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
