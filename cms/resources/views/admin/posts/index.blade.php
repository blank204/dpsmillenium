@extends('admin.layout')
@section('title', 'All posts')
@section('content')
<div data-screen="posts-index">
    <h1>All posts</h1>
    <form method="get" data-region="filters">
        <label>Section <select name="section"><option value="">All</option>@foreach ($sections as $s)<option value="{{ $s->value }}" @selected(request('section') === $s->value)>{{ $s->label() }}</option>@endforeach</select></label>
        <label>Status <select name="status">@foreach (['' => 'All', 'draft' => 'Draft', 'scheduled' => 'Scheduled', 'live' => 'Live', 'hidden' => 'Removed from site'] as $v => $l)<option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>@endforeach</select></label>
        <label>Search titles <input type="search" name="q" value="{{ request('q') }}"></label>
        <button>Filter</button>
    </form>

    <table data-region="posts-table">
        <thead><tr><th>Title</th><th>Section</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse ($posts as $post)
            <tr data-state="{{ $post->adminStatus() }}">
                <td><a href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a>@if ($post->pinned_until && $post->pinned_until->isFuture()) (pinned)@endif</td>
                <td>{{ $post->section->label() }}</td>
                <td>{{ ucfirst($post->adminStatus()) }}</td>
                <td>{{ ($post->sort_at ?? $post->updated_at)->format('j M Y') }}</td>
                <td>
                    <a href="{{ route('admin.posts.edit', $post) }}">Edit</a>
                    <details><summary>Duplicate</summary><form method="post" action="{{ route('admin.posts.duplicate', $post) }}">@csrf @include('admin.partials.actor-name')<button>Make a copy</button></form></details>
                    <details><summary>Delete</summary><form method="post" action="{{ route('admin.posts.destroy', $post) }}">@csrf @method('DELETE') <p>Moves to the recycle bin. IT can restore it for {{ config('cms.recycle_days') }} days.</p>@include('admin.partials.actor-name')<button>Delete</button></form></details>
                </td>
            </tr>
        @empty
            <tr data-state="empty"><td colspan="5">No posts match these filters.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $posts->links() }}
</div>
@endsection
