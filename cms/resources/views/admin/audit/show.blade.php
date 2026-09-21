@extends('admin.layout')
@section('title', 'Change #'.$entry->id)
@section('content')
@php($fmt = fn ($v) => is_scalar($v) || $v === null ? (string) ($v ?? '—') : json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
<div data-screen="audit-show">
    <a class="back-link" href="{{ route('admin.audit.index') }}">← Back to the change log</a>
    <h1 class="h1">Entry #{{ $entry->id }}</h1>
    <p class="lede"><code>{{ $entry->action }}</code> · {{ $entry->target_type ? $entry->target_type.' #'.$entry->target_id : 'no item' }}</p>

    <div class="cards-2">
        <section class="card">
            <h2 class="eyebrow">Who and when</h2>
            <dl class="kv" data-region="audit-meta">
                <dt>When</dt><dd>{{ $entry->occurred_at->format('j M Y, g:i:s A') }} (Qatar time)</dd>
                <dt>Typed name</dt><dd>{{ $entry->actor_name ?? '—' }}</dd>
                <dt>Login used</dt><dd>{{ $entry->account_role ?? '—' }}</dd>
                <dt>IP address</dt><dd>{{ $entry->ip_address ?? '—' }}</dd>
                <dt>Device</dt><dd>{{ $entry->user_agent ?? '—' }}</dd>
                <dt>Session</dt><dd><span class="mono">{{ $entry->session_id ?? '—' }}</span></dd>
                <dt>Entry hash</dt><dd><span class="mono">{{ $entry->hash }}</span></dd>
            </dl>
        </section>

        <section class="card">
            <h2 class="eyebrow">What changed</h2>
            @forelse ($diff as $field => $change)
                <div class="diff">
                    <span class="diff-field">{{ $field }}</span>
                    <div class="diff-before">{{ $fmt($change['before']) }}</div>
                    <div class="diff-after">{{ $fmt($change['after']) }}</div>
                </div>
            @empty
                <p data-state="empty" class="meta mt">No field changes recorded for this action.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
