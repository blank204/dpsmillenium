<!doctype html>
<html lang="en" class="no-js">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'DPS Millennium International School')</title>
    <meta name="description" content="@yield('meta_description', 'DPS Millennium International School, Al Wukair, Qatar. A CBSE school opening April 2027.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('site/assets/shield.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="{{ url('/') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ asset('site/assets/fonts/plex-sans-400.woff2') }}">
    <link rel="preload" as="font" type="font/woff2" crossorigin href="{{ asset('site/assets/fonts/plex-sans-600.woff2') }}">
    <link rel="stylesheet" href="{{ asset('site/site.css') }}">
    @stack('styles')
    <meta property="og:site_name" content="DPS Millennium International School">
    <meta property="og:title" content="@yield('title', 'DPS Millennium International School')">
    <meta property="og:description" content="@yield('meta_description', 'DPS Millennium International School, Al Wukair, Qatar. A CBSE school opening April 2027.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
</head>
<body>
<a class="skip" href="#main">Skip to the main content</a>

@include('site.partials.nav')

<main id="main" @class(['page' => ! ($marketing ?? false)])>
    @yield('content')
</main>

@include('site.partials.footer')

<script src="{{ asset('site/site.js') }}" defer></script>
@stack('scripts')
</body>
</html>
