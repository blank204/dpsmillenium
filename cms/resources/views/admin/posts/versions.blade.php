@extends('admin.layout')
@section('title', 'Versions')
@section('content')
<div data-screen="post-versions">
    <h1>Version history: {{ $post->title }}</h1>
    <p><a href="{{ route('admin.posts.edit', $post) }}">← Back to post</a> · <a href="{{ route('admin.audit.index', ['post' => $post->id]) }}">Change log for this post</a></p>
    @include('admin.partials.errors')
    @foreach ($versions as $v)
        <details data-version="{{ $v->version }}">
            <summary>Version {{ $v->version }} · {{ $v->created_at->format('j M Y, g:i A') }} · by {{ $v->actor_name ?? '—' }} · "{{ $v->data['title'] ?? '' }}"</summary>
            @include('admin.partials.blocks', ['blocks' => $v->data['blocks'] ?? []])
            <form method="post" action="{{ route('admin.posts.versions.restore', [$post, $v]) }}">@csrf @include('admin.partials.actor-name')<button>Restore this version</button></form>
        </details>
    @endforeach
</div>
@endsection
