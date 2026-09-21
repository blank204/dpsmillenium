@extends('admin.layout')
@section('title', 'Versions')
@section('content')
<div data-screen="post-versions">
    <a class="back-link" href="{{ route('admin.posts.edit', $post) }}">← Back to the post</a>
    <h1 class="h1">Version history</h1>
    <p class="lede">{{ $post->title }} · {{ $versions->count() }} {{ \Illuminate\Support\Str::plural('save', $versions->count()) }} kept. Restoring writes a new version, it does not erase anything.</p>
    <p class="mt"><a href="{{ route('admin.audit.index', ['post' => $post->id]) }}">Change log for this post →</a></p>

    @include('admin.partials.errors')

    <div class="stack mt-lg">
        @forelse ($versions as $v)
            <section class="card" data-version="{{ $v->version }}">
                <div class="split">
                    <div>
                        <b class="h2">Version {{ $v->version }}</b>
                        @if ($loop->first)<span class="badge badge-current">Current</span>@endif
                        <p class="meta">{{ $v->created_at->format('j M Y, g:i A') }} · by {{ $v->actor_name ?? '—' }}</p>
                        <p class="meta">“{{ $v->data['title'] ?? '' }}”</p>
                    </div>
                    @unless ($loop->first)
                        <form method="post" action="{{ route('admin.posts.versions.restore', [$post, $v]) }}" class="grid-2" style="align-items:end;flex:0 1 20rem">
                            @csrf
                            @include('admin.partials.actor-name', ['compact' => true])
                            <div><button class="btn btn-sm">Restore this version</button></div>
                        </form>
                    @endunless
                </div>
                <details style="margin-top:.8rem">
                    <summary class="btn btn-sm">What was in it</summary>
                    <div class="mt public-article">@include('admin.partials.blocks', ['blocks' => $v->data['blocks'] ?? []])</div>
                </details>
            </section>
        @empty
            <div class="empty"><p>No earlier versions yet.</p><p>A version is kept each time this post is saved.</p></div>
        @endforelse
    </div>
</div>
@endsection
