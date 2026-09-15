@extends('admin.layout')
@section('title', 'Dashboard')
@section('content')
<div data-screen="dashboard">
    <h1>Dashboard</h1>
    <p data-region="primary-actions">
        <a href="{{ route('admin.posts.choose') }}">+ New post</a>
        <a href="{{ route('admin.calendar.index') }}">+ Calendar entry</a>
    </p>

    <section data-region="scheduled"><h2>Scheduled</h2>
        @forelse ($scheduled as $post)
            <p data-state="scheduled"><a href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a> · {{ $post->section->label() }} · goes live {{ $post->publish_at->format('j M Y, g:i A') }} ({{ $post->publish_at->diffForHumans() }})</p>
        @empty <p data-state="empty">Nothing scheduled.</p>
        @endforelse
    </section>

    <section data-region="pinned"><h2>Pinned</h2>
        @forelse ($pinned as $post)
            <div data-state="pinned">
                <a href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a> · unpins {{ $post->pinned_until->format('j M Y, g:i A') }}
                <form method="post" action="{{ route('admin.posts.unpin', $post) }}">@csrf @include('admin.partials.actor-name')<button>Unpin now</button></form>
            </div>
        @empty <p data-state="empty">Nothing pinned.</p>
        @endforelse
    </section>

    <section data-region="drafts"><h2>Drafts</h2>
        @forelse ($drafts as $post)
            <p data-state="draft"><a href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a> · {{ $post->section->label() }} · saved {{ $post->updated_at->diffForHumans() }}</p>
        @empty <p data-state="empty">No drafts.</p>
        @endforelse
    </section>

    <section data-region="recent"><h2>Recently published</h2>
        @forelse ($recent as $post)
            <p data-state="live"><a href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a> · {{ $post->section->label() }} · {{ $post->sort_at->format('j M Y') }}</p>
        @empty <p data-state="empty">Nothing published yet. Start with “New post”.</p>
        @endforelse
    </section>
</div>
@endsection
