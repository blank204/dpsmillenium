@extends('admin.layout')
@section('title', 'Recycle bin')
@section('content')
<div data-screen="recycle-bin">
    <h1>Recycle bin</h1>
    <p>Deleted posts stay here for {{ $days }} days, then are removed permanently. Only IT (Super admin) can restore them.</p>
    <table>
        <thead><tr><th>Title</th><th>Section</th><th>Deleted</th><th>Removed permanently</th><th></th></tr></thead>
        <tbody>
        @forelse ($posts as $post)
            <tr data-state="deleted">
                <td>{{ $post->title }}</td><td>{{ $post->section->label() }}</td>
                <td>{{ $post->deleted_at->format('j M Y, g:i A') }}</td>
                <td>{{ $post->deleted_at->addDays($days)->format('j M Y') }}</td>
                <td>@if (auth()->user()->isSuperAdmin() && Route::has('admin.recycle.restore'))<form method="post" action="{{ route('admin.recycle.restore', $post->id) }}">@csrf @include('admin.partials.actor-name')<button>Restore</button></form>@endif</td>
            </tr>
        @empty
            <tr data-state="empty"><td colspan="5">The recycle bin is empty.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
