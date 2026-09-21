<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CMS') · DPS Millennium CMS</title>
    <link rel="icon" href="{{ asset('img/shield.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('admin/admin.css') }}">
</head>
<body>
@auth
    @php($super = auth()->user()->isSuperAdmin())
    <header class="topbar">
        <a class="topbar-brand" href="{{ route('admin.dashboard') }}">
            <img src="{{ asset('img/shield.svg') }}" alt="">
            <span>
                <b>DPS Millennium</b>
                <span><span>Staff content manager</span></span>
            </span>
        </a>
        <div class="topbar-right">
            <span class="topbar-who">Signed in as <b data-role="{{ auth()->user()->role }}">{{ $super ? 'Super admin' : 'Editor' }}</b></span>
            <form method="post" action="{{ route('admin.logout') }}">@csrf<button class="linkish">Log out</button></form>
        </div>
    </header>

    <div class="shell">
        <nav class="sidebar" data-region="admin-nav" aria-label="Sections">
            @php($nav = [
                'admin.dashboard'    => 'Dashboard',
                'admin.posts.choose' => 'New post',
                'admin.posts.index'  => 'All posts',
                'admin.calendar.index' => 'Calendar',
                'admin.recycle.index'  => 'Recycle bin',
            ])
            @foreach ($nav as $name => $label)
                @if (Route::has($name))
                    <a href="{{ route($name) }}" @if (request()->routeIs($name)) aria-current="page" @endif>{{ $label }}</a>
                @endif
            @endforeach

            @if ($super)
                <p class="sidebar-group">School IT</p>
                @foreach ([
                    'admin.audit.index'    => 'Change log',
                    'admin.settings.edit'  => 'Settings',
                    'admin.security.edit'  => 'Security',
                    'admin.pages.index'    => 'Custom pages',
                    'admin.system.show'    => 'System',
                ] as $name => $label)
                    @if (Route::has($name))
                        <a href="{{ route($name) }}" @if (request()->routeIs($name)) aria-current="page" @endif>{{ $label }}</a>
                    @endif
                @endforeach
            @endif

            <p class="sidebar-group">Help</p>
            @if (Route::has('admin.tutorial'))
                <a href="{{ route('admin.tutorial') }}" @if (request()->routeIs('admin.tutorial')) aria-current="page" @endif>How to post</a>
            @endif
            @if (Route::has('home'))
                <a href="{{ route('home') }}" target="_blank" rel="noopener">View the website ↗</a>
            @endif
        </nav>

        <main class="content">
            @if (session('status'))<p class="notice notice-good flash" role="status">{{ session('status') }}</p>@endif
            @yield('content')
        </main>
    </div>
@else
    @yield('content')
@endauth
@if (session('clear_autosave'))<span hidden data-clear-autosave="{{ session('clear_autosave') }}"></span>@endif
@stack('scripts')
</body>
</html>
