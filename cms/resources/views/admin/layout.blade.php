<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CMS') · DPS Millennium CMS</title>
    <style>body{font-family:system-ui,sans-serif;max-width:960px;margin:0 auto;padding:16px;line-height:1.5}label{display:block;margin-top:.75rem}input,select,textarea{font:inherit;max-width:100%}.error{color:#b00020}.flash{padding:.5rem;border:1px solid #888}</style>
</head>
<body>
@auth
    <nav data-region="admin-nav">
        <a href="{{ route('admin.dashboard') }}">Dashboard</a>
        <a href="{{ route('admin.posts.choose') }}">New post</a>
        <a href="{{ route('admin.posts.index') }}">All posts</a>
        <a href="{{ route('admin.calendar.index') }}">Calendar</a>
        <a href="{{ route('admin.recycle.index') }}">Recycle bin</a>
        @if (auth()->user()->isSuperAdmin())
            @foreach (['admin.audit.index' => 'Change log', 'admin.settings.edit' => 'Settings', 'admin.security.edit' => 'Security', 'admin.pages.index' => 'Custom pages', 'admin.system.show' => 'System'] as $routeName => $label)
                @if (Route::has($routeName))<a href="{{ route($routeName) }}">{{ $label }}</a>@endif
            @endforeach
        @endif
        <form method="post" action="{{ route('admin.logout') }}" style="display:inline">@csrf<button>Log out</button></form>
        <span data-role="{{ auth()->user()->role }}">Signed in as {{ auth()->user()->isSuperAdmin() ? 'Super admin' : 'Editor' }}</span>
    </nav>
@endauth
@if (session('status'))<p class="flash" role="status">{{ session('status') }}</p>@endif
@if (session('clear_autosave'))<span hidden data-clear-autosave="{{ session('clear_autosave') }}"></span>@endif
<main>@yield('content')</main>
@stack('scripts')
</body>
</html>
