@extends('admin.layout')
@section('title', 'Custom pages')
@section('content')
<div data-screen="custom-pages">
    <h1>Custom HTML pages</h1>
    <p>For pages the normal blocks cannot do (for example searchable seating plans). They run isolated from the website and cannot read logins or change other pages. Only IT can upload them.</p>
    @include('admin.partials.errors')
    <form method="post" action="{{ route('admin.pages.store') }}" enctype="multipart/form-data" data-region="upload-page">@csrf
        <label>Title <input name="title" required maxlength="150"></label>
        <label>HTML file (max 2 MB) <input type="file" name="file" accept=".html,.htm,text/html" required></label>
        @include('admin.partials.actor-name')
        <button>Upload</button>
    </form>
    <table data-region="pages-table">
        <thead><tr><th>ID</th><th>Title</th><th>Size</th><th>Uploaded</th><th></th></tr></thead>
        <tbody>
        @forelse ($pages as $page)
            <tr><td>#{{ $page->id }}</td><td><a href="{{ route('pages.sandboxed', $page->token) }}" target="_blank" rel="noopener">{{ $page->title }}</a></td><td>{{ number_format($page->bytes / 1024, 0) }} KB</td><td>{{ $page->created_at->format('j M Y') }}</td>
                <td><details><summary>Remove</summary><form method="post" action="{{ route('admin.pages.destroy', $page) }}">@csrf @method('DELETE') @include('admin.partials.actor-name')<button>Remove</button></form></details></td></tr>
        @empty
            <tr data-state="empty"><td colspan="5">No custom pages yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
