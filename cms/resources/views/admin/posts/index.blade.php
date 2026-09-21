@extends('admin.layout')
@section('title', 'All posts')
@section('content')
@php($statusLabels = ['' => 'Any status', 'draft' => 'Draft', 'scheduled' => 'Scheduled', 'live' => 'Live', 'hidden' => 'Removed from site'])
<div data-screen="posts-index">
    <div class="split">
        <div>
            <h1 class="h1">All posts</h1>
            <p class="lede">Everything ever written, whatever state it is in.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.posts.choose') }}">New post</a>
    </div>

    <form method="get" class="filters" data-region="filters">
        <label class="field">
            <span>Section</span>
            <select name="section">
                <option value="">All sections</option>
                @foreach ($sections as $s)
                    <option value="{{ $s->value }}" @selected(request('section') === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </label>
        <label class="field">
            <span>Status</span>
            <select name="status">
                @foreach ($statusLabels as $v => $l)
                    <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
                @endforeach
            </select>
        </label>
        <label class="field">
            <span>Search titles</span>
            <input type="search" name="q" value="{{ request('q') }}">
        </label>
        <button class="btn">Filter</button>
        @if (request()->hasAny(['section', 'status', 'q']))
            <a class="btn" href="{{ route('admin.posts.index') }}">Clear</a>
        @endif
    </form>

    @if ($posts->isEmpty())
        <div class="empty" data-state="empty">
            <p>No posts match these filters.</p>
            <p>Try clearing the search box, or choose “Any status”.</p>
        </div>
    @else
        <div class="table-wrap">
            <table class="table" data-region="posts-table">
                <thead><tr><th>Title</th><th>Section</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
                <tbody>
                @foreach ($posts as $post)
                    @php($status = $post->adminStatus())
                    <tr data-state="{{ $status }}">
                        <td>
                            @if ($post->pinned_until && $post->pinned_until->isFuture())
                                <span class="badge badge-pin">Pinned</span>
                            @endif
                            <a class="row-title" href="{{ route('admin.posts.edit', $post) }}">{{ $post->title }}</a>
                        </td>
                        <td class="meta">{{ $post->section->label() }}</td>
                        <td><span class="badge badge-{{ $status }}">{{ $statusLabels[$status] ?? ucfirst($status) }}</span></td>
                        <td class="meta nowrap">{{ ($post->sort_at ?? $post->updated_at)->format('j M Y') }}</td>
                        <td>
                            <div class="btn-row-tight">
                                <a class="btn btn-sm" href="{{ route('admin.posts.edit', $post) }}">Edit</a>
                                <details>
                                    <summary class="btn btn-sm">Duplicate</summary>
                                    <form method="post" action="{{ route('admin.posts.duplicate', $post) }}" class="card" style="margin-top:.4rem">
                                        @csrf
                                        <p class="meta">Makes an unpublished copy you can edit.</p>
                                        @include('admin.partials.actor-name')
                                        <p class="btn-row mt"><button class="btn btn-sm">Make a copy</button></p>
                                    </form>
                                </details>
                                <details>
                                    <summary class="btn btn-sm btn-danger">Delete…</summary>
                                    <form method="post" action="{{ route('admin.posts.destroy', $post) }}" class="card" style="margin-top:.4rem">
                                        @csrf @method('DELETE')
                                        <p class="meta">Moves to the recycle bin. IT can restore it for {{ config('cms.recycle_days') }} days.</p>
                                        @include('admin.partials.actor-name')
                                        <p class="btn-row mt"><button class="btn btn-sm btn-danger">Delete</button></p>
                                    </form>
                                </details>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="pager">
            <p class="meta">Showing {{ $posts->firstItem() }}–{{ $posts->lastItem() }} of {{ $posts->total() }}</p>
            {{ $posts->links() }}
        </div>

        <p class="notice notice-info">Deleting asks for your name. Deleted posts go to the recycle bin for {{ config('cms.recycle_days') }} days — only IT can restore them.</p>
    @endif
</div>
@endsection
