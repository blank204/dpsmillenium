@extends('admin.layout')
@section('title', 'Dashboard')
@section('content')
<div data-screen="dashboard">
    <h1 class="h1">Dashboard</h1>
    <p class="lede">Post something, or check what is waiting to go live.</p>

    <p class="btn-row mt" data-region="primary-actions">
        <a class="btn btn-primary btn-lg" href="{{ route('admin.posts.choose') }}">New post</a>
        <a class="btn btn-lg" href="{{ route('admin.calendar.index') }}">Calendar entry</a>
    </p>

    <div class="cards">
        <section class="card" data-region="scheduled">
            <h2 class="eyebrow">Scheduled</h2>
            <div class="card-list">
                @forelse ($scheduled as $post)
                    <div data-state="scheduled">
                        <a class="row-title" href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a>
                        <p class="meta">{{ $post->section->label() }}</p>
                        <p class="meta" style="color:var(--info-ink);font-weight:500">Goes live {{ $post->publish_at->format('j M Y, g:i A') }} · {{ $post->publish_at->diffForHumans() }}</p>
                    </div>
                @empty
                    <p data-state="empty" class="meta">Nothing scheduled.</p>
                @endforelse
            </div>
        </section>

        <section class="card" data-region="pinned">
            <h2 class="eyebrow">Pinned</h2>
            <div class="card-list">
                @forelse ($pinned as $post)
                    <div data-state="pinned">
                        <a class="row-title" href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a>
                        <p class="meta">{{ $post->section->label() }} · unpins {{ $post->pinned_until->format('j M Y, g:i A') }}</p>
                        <form method="post" action="{{ route('admin.posts.unpin', $post) }}" class="grid-2" style="margin-top:.5rem;align-items:end">
                            @csrf
                            @include('admin.partials.actor-name', ['compact' => true])
                            <div><button class="btn btn-sm">Unpin now</button></div>
                        </form>
                    </div>
                @empty
                    <p data-state="empty" class="meta">Nothing pinned.</p>
                @endforelse
            </div>
        </section>

        <section class="card" data-region="drafts">
            <h2 class="eyebrow">Drafts <span class="muted" style="letter-spacing:0;text-transform:none">({{ $drafts->count() }})</span></h2>
            <div class="card-list card-scroll">
                @forelse ($drafts as $post)
                    <div data-state="draft">
                        <a class="row-title" href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a>
                        <p class="meta">{{ $post->section->label() }} · saved {{ $post->updated_at->diffForHumans() }}</p>
                    </div>
                @empty
                    <p data-state="empty" class="meta">No drafts.</p>
                @endforelse
            </div>
        </section>

        <section class="card" data-region="recent">
            <h2 class="eyebrow">Recently published</h2>
            <div class="card-list">
                @forelse ($recent as $post)
                    <div data-state="live">
                        <a class="row-title" href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a>
                        <p class="meta">{{ $post->section->label() }} · {{ $post->sort_at->format('j M Y') }}</p>
                    </div>
                @empty
                    <p data-state="empty" class="meta">Nothing published yet. Start with “New post”.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
