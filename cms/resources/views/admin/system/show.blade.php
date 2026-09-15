@extends('admin.layout')
@section('title', 'System')
@section('content')
<div data-screen="system">
    <h1>System status</h1>
    <dl>
        <dt>PHP</dt><dd>{{ $php }}</dd>
        <dt>Database</dt><dd>{{ $db }}</dd>
        <dt>Image library</dt><dd>{{ $imageDriver }}</dd>
        <dt>PHP extensions</dt><dd>@foreach ($extensions as $ext => $ok){{ $ext }}: {{ $ok ? 'yes' : 'NO' }}@if (! $loop->last), @endif @endforeach</dd>
        <dt>Scheduled tasks</dt><dd>{{ $schedulerSource ? 'Last run '.\Carbon\CarbonImmutable::parse($schedulerLastRun)->diffForHumans().' via '.$schedulerSource : 'Never run yet' }}{{ $schedulerSource === 'visitor' ? ' (no cron detected; posts still go live on time when anyone visits)' : '' }}</dd>
        <dt>Uploaded files</dt><dd>{{ $storageMb }} MB</dd>
        <dt>Backups</dt><dd>Not set up yet (Phase 3)</dd>
    </dl>
</div>
@endsection
