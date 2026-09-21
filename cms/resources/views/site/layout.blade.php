<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'DPS Millennium') · DPS Millennium</title>
    <meta name="description" content="@yield('meta_description', 'Notices, circulars, events and the school calendar for DPS Millennium, Doha.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="icon" href="{{ asset('img/shield.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('site/site.css') }}">
    <meta property="og:site_name" content="DPS Millennium">
    <meta property="og:title" content="@yield('title', 'DPS Millennium')">
    <meta property="og:description" content="@yield('meta_description', 'Notices, circulars, events and the school calendar for DPS Millennium, Doha.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    @stack('head')
</head>
<body>
<a class="skip" href="#main">Skip to the main content</a>

<header class="site-head">
    <div class="wrap site-head-inner">
        <a class="site-brand" href="{{ route('home') }}">
            <img src="{{ asset('img/shield.svg') }}" alt="">
            <span>
                <b>DPS Millennium</b>
                <span><span>Doha, Qatar</span></span>
            </span>
        </a>
        <nav class="site-nav" aria-label="Main">
            <a href="{{ route('home') }}" @if (request()->routeIs('home')) aria-current="page" @endif>Home</a>
            @foreach (\App\Content\Section::cases() as $s)
                <a href="{{ route('section', $s->pathSegment()) }}"
                   @if (request()->routeIs('section') && request()->route('section') === $s->pathSegment()) aria-current="page" @endif>{{ $s->label() }}</a>
            @endforeach
            <a href="{{ route('calendar') }}" @if (request()->routeIs('calendar')) aria-current="page" @endif>School Calendar</a>
            <a href="{{ route('archive') }}" @if (request()->routeIs('archive')) aria-current="page" @endif>Archive</a>
        </nav>
    </div>
</header>

<main id="main">
    @yield('content')
</main>

<footer class="site-foot">
    <div class="wrap site-foot-inner">
        <div>
            <h2>Sections</h2>
            <ul>
                @foreach (\App\Content\Section::cases() as $s)
                    <li><a href="{{ route('section', $s->pathSegment()) }}">{{ $s->label() }}</a></li>
                @endforeach
            </ul>
        </div>
        <div>
            <h2>Find things</h2>
            <ul>
                <li><a href="{{ route('calendar') }}">School calendar</a></li>
                <li><a href="{{ route('archive') }}">Archive of everything</a></li>
            </ul>
        </div>
        <div>
            <h2>DPS Millennium</h2>
            <p>Doha, Qatar</p>
            <p style="margin-top:.6rem">© {{ now()->year }} DPS Millennium</p>
        </div>
    </div>
</footer>
</body>
</html>
