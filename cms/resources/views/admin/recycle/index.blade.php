@extends('admin.layout')
@section('title', 'Recycle bin')
@section('content')
@php($canRestore = auth()->user()->isSuperAdmin() && Route::has('admin.recycle.restore'))
<div data-screen="recycle-bin">
    <h1 class="h1">Recycle bin</h1>
    <p class="lede">Deleted posts stay here for {{ $days }} days, then go for good.</p>

    @unless ($canRestore)
        <p class="notice notice-info">You can see what was deleted, but restoring is an IT job. Ask school IT and tell them the post title.</p>
    @endunless

    @include('admin.partials.errors')

    @if ($posts->isEmpty())
        <div class="empty" data-state="empty">
            <p>The recycle bin is empty.</p>
            <p>Deleted posts will appear here for {{ $days }} days.</p>
        </div>
    @else
        <div class="stack mt-lg">
            @foreach ($posts as $post)
                <div class="card split" data-state="deleted">
                    <div>
                        <b class="row-title">{{ $post->title }}</b>
                        <p class="meta">{{ $post->section->label() }} · deleted {{ $post->deleted_at->format('j M Y, g:i A') }}</p>
                        <p class="meta">Removed permanently on {{ $post->deleted_at->addDays($days)->format('j M Y') }}</p>
                    </div>
                    @if ($canRestore)
                        <form method="post" action="{{ route('admin.recycle.restore', $post->id) }}" class="grid-2" style="align-items:end;flex:0 1 20rem">
                            @csrf
                            @include('admin.partials.actor-name', ['compact' => true])
                            <div><button class="btn btn-sm">Restore</button></div>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
