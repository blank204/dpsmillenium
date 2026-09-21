@extends('admin.layout')
@section('title', 'Custom pages')
@section('content')
<div data-screen="custom-pages">
    <h1 class="h1">Custom pages</h1>
    <p class="lede">Standalone HTML pages that editors can link to from a post — for things the normal blocks cannot do, like a searchable seating plan. They run isolated from the website and cannot read logins or change other pages. IT only.</p>

    @include('admin.partials.errors')

    <section class="card card-lg mt-lg" data-region="upload-page">
        <h2 class="eyebrow">Upload a page</h2>
        <form method="post" action="{{ route('admin.pages.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid-2">
                <label class="field">
                    <span>Page title</span>
                    <input name="title" required maxlength="150">
                </label>
                <label class="field">
                    <span>HTML file (max 2 MB)</span>
                    <input type="file" name="file" accept=".html,.htm,text/html" required>
                </label>
                @include('admin.partials.actor-name')
            </div>
            <p class="btn-row mt"><button class="btn btn-primary">Upload</button></p>
        </form>
        <p class="notice notice-warn">Scripts in the file are kept as they are. Only upload pages you wrote or trust.</p>
    </section>

    @if ($pages->isEmpty())
        <div class="empty" data-state="empty">
            <p>No custom pages yet.</p>
            <p>Upload one above, then editors can link to it from a post.</p>
        </div>
    @else
        <div class="stack mt-lg" data-region="pages-table">
            @foreach ($pages as $page)
                <div class="card split">
                    <div>
                        <b class="row-title">#{{ $page->id }} {{ $page->title }}</b>
                        <p class="meta">{{ number_format($page->bytes / 1024, 0) }} KB · uploaded {{ $page->created_at->format('j M Y') }}</p>
                    </div>
                    <div class="btn-row-tight">
                        <a class="btn btn-sm" href="{{ route('pages.sandboxed', $page->token) }}" target="_blank" rel="noopener">Open ↗</a>
                        <details>
                            <summary class="btn btn-sm">Replace file</summary>
                            <form method="post" action="{{ route('admin.pages.replace', $page) }}" enctype="multipart/form-data" class="card" style="margin-top:.4rem;min-width:18rem">
                                @csrf
                                <p class="meta">Keeps the same link, so posts that use this page keep working.</p>
                                <label class="field"><span>New HTML file</span><input type="file" name="file" accept=".html,.htm,text/html" required></label>
                                @include('admin.partials.actor-name')
                                <p class="btn-row mt"><button class="btn btn-sm">Replace</button></p>
                            </form>
                        </details>
                        <details>
                            <summary class="btn btn-sm btn-danger">Delete…</summary>
                            <form method="post" action="{{ route('admin.pages.destroy', $page) }}" class="card" style="margin-top:.4rem;min-width:18rem">
                                @csrf @method('DELETE')
                                <p class="meta">Posts that used this page will stop showing it.</p>
                                @include('admin.partials.actor-name')
                                <p class="btn-row mt"><button class="btn btn-sm btn-danger">Delete</button></p>
                            </form>
                        </details>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
