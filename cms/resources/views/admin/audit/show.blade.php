@extends('admin.layout')
@section('title', 'Change #'.$entry->id)
@section('content')
@php($fmt = fn ($v) => is_scalar($v) || $v === null ? (string) ($v ?? '—') : json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
<div data-screen="audit-show">
    <h1>Change #{{ $entry->id }}: {{ $entry->action }}</h1>
    <dl data-region="audit-meta">
        <dt>When</dt><dd>{{ $entry->occurred_at->format('j M Y, g:i:s A') }} (Qatar time)</dd>
        <dt>Name typed</dt><dd>{{ $entry->actor_name ?? '—' }}</dd>
        <dt>Login used</dt><dd>{{ $entry->account_role ?? '—' }}</dd>
        <dt>IP address</dt><dd>{{ $entry->ip_address ?? '—' }}</dd>
        <dt>Device / browser</dt><dd>{{ $entry->user_agent ?? '—' }}</dd>
        <dt>Session</dt><dd><code>{{ $entry->session_id ?? '—' }}</code></dd>
        <dt>Item</dt><dd>{{ $entry->target_type ? $entry->target_type.' #'.$entry->target_id : '—' }}</dd>
        <dt>Integrity hash</dt><dd><code>{{ $entry->hash }}</code></dd>
    </dl>
    <h2>What changed</h2>
    <table data-region="diff">
        <thead><tr><th>Field</th><th>Before</th><th>After</th></tr></thead>
        <tbody>
        @forelse ($diff as $field => $change)
            <tr><th scope="row">{{ $field }}</th><td><pre>{{ $fmt($change['before']) }}</pre></td><td><pre>{{ $fmt($change['after']) }}</pre></td></tr>
        @empty
            <tr data-state="empty"><td colspan="3">No field changes recorded for this action.</td></tr>
        @endforelse
        </tbody>
    </table>
    <p><a href="{{ route('admin.audit.index') }}">← Back to change log</a></p>
</div>
@endsection
