@extends('admin.layout')
@section('title', 'System')
@section('content')
@php($missing = collect($extensions)->reject(fn ($ok) => $ok)->keys())
@php($lastRun = $schedulerLastRun ? \Carbon\CarbonImmutable::parse($schedulerLastRun) : null)
@php($cronStale = $lastRun === null || $lastRun->lessThan(now()->subDay()))
<div data-screen="system">
    <h1 class="h1">System</h1>
    <p class="lede">What the server has, and whether the scheduled job is running.</p>

    @if ($missing->isNotEmpty() || $cronStale)
        <div class="notice notice-warn">
            <b>Some things need the hosting provider</b>
            @if ($missing->isNotEmpty())
                <p>Missing PHP {{ \Illuminate\Support\Str::plural('extension', $missing->count()) }}: <code>{{ $missing->implode(', ') }}</code>. Ask the host to enable {{ $missing->count() === 1 ? 'it' : 'them' }}.</p>
            @endif
            @if ($cronStale)
                <p>The scheduled job {{ $lastRun ? 'has not run since '.$lastRun->diffForHumans() : 'has never run' }}. Scheduled posts still go live when somebody visits the website, but a real cron job is more reliable — ask the host to run <code>php artisan schedule:run</code> every minute.</p>
            @endif
        </div>
    @else
        <p class="notice notice-good">Everything the site needs is present, and the scheduled job ran {{ $lastRun->diffForHumans() }}.</p>
    @endif

    <div class="cards-2">
        <section class="card">
            <h2 class="eyebrow">PHP extensions</h2>
            <div class="card-list">
                @foreach ($extensions as $ext => $ok)
                    <div class="split"><span>{{ $ext }}</span><span class="{{ $ok ? 'ext-ok' : 'ext-missing' }}">{{ $ok ? 'Present' : 'Missing' }}</span></div>
                @endforeach
            </div>
        </section>

        <section class="card">
            <h2 class="eyebrow">Environment</h2>
            <dl class="kv">
                <dt>PHP</dt><dd>{{ $php }}</dd>
                <dt>Database</dt><dd>{{ $db }}</dd>
                <dt>Image library</dt><dd>{{ $imageDriver }}</dd>
                <dt>Uploaded files</dt><dd>{{ $storageMb }} MB</dd>
                <dt>Max upload size</dt><dd>{{ ini_get('upload_max_filesize') }} (PHP) · {{ (int) (config('cms.limits.video_bytes') / 1048576) }} MB (CMS limit)</dd>
                <dt>Server time</dt><dd>{{ now()->format('j M Y, g:i A') }} (Qatar)</dd>
                <dt>Backups</dt><dd>Not set up yet</dd>
            </dl>
        </section>

        <section class="card">
            <h2 class="eyebrow">Scheduled job</h2>
            <p class="meta mt">Publishes scheduled posts, unpins expired pins and empties the recycle bin after {{ config('cms.recycle_days') }} days.</p>
            <dl class="kv">
                <dt>Last run</dt><dd>{{ $lastRun ? $lastRun->format('j M Y, g:i A').' ('.$lastRun->diffForHumans().')' : 'Never' }}</dd>
                <dt>Triggered by</dt><dd>{{ $schedulerSource ?: '—' }}{{ $schedulerSource === 'visitor' ? ' — no cron detected' : '' }}</dd>
            </dl>
            <form method="post" action="{{ route('admin.system.run-due') }}" class="mt">
                @csrf
                @include('admin.partials.actor-name')
                <p class="btn-row mt"><button class="btn">Run it now</button></p>
            </form>
            <p class="hint">Safe to press at any time.</p>
        </section>
    </div>
</div>
@endsection
