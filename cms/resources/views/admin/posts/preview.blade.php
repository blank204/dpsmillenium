@extends('admin.layout')
@section('title', 'Preview')
@section('content')
<div data-screen="post-preview">
    <div class="preview-bar">
        <div>
            <h1 class="h1">Parent view</h1>
            <p class="lede">What families see on the public page. Nothing here is saved.</p>
        </div>
        <div class="btn-row-tight" data-region="preview-widths">
            <button type="button" class="btn btn-sm" onclick="document.getElementById('preview-frame').classList.add('is-phone')">Phone</button>
            <button type="button" class="btn btn-sm" onclick="document.getElementById('preview-frame').classList.remove('is-phone')">Desktop</button>
        </div>
    </div>

    @if (! empty($errors_list))
        <div data-region="form-errors" class="notice notice-error" role="alert">
            <b>This post cannot go live yet</b>
            <ul>@foreach ($errors_list as $messages)@foreach ($messages as $m)<li>{{ $m }}</li>@endforeach @endforeach</ul>
        </div>
    @else
        <div class="preview-stage">
            <article id="preview-frame" class="preview-paper public-article" data-region="preview-article">
                <p class="eyebrow">{{ $post->section->label() }}</p>
                <h1 class="public-title">{{ $post->title }}</h1>
                @if ($post->summary)<p class="public-summary">{{ $post->summary }}</p>@endif
                <div class="divider"></div>
                @include('admin.partials.blocks', ['blocks' => $post->blocks])
            </article>
        </div>
    @endif
</div>
@endsection
