@extends('admin.layout')
@section('title', 'Preview')
@section('content')
<div data-screen="post-preview">
    <p data-region="preview-widths"><button type="button" onclick="document.getElementById('preview-frame').style.maxWidth='390px'">Phone</button> <button type="button" onclick="document.getElementById('preview-frame').style.maxWidth='none'">Desktop</button></p>
    @if (! empty($errors_list))
        <div class="error" role="alert"><p>This post has problems:</p><ul>@foreach ($errors_list as $messages)@foreach ($messages as $m)<li>{{ $m }}</li>@endforeach @endforeach</ul></div>
    @else
        <article id="preview-frame" data-region="preview-article">
            <p>{{ $post->section->label() }}</p>
            <h1>{{ $post->title }}</h1>
            @if ($post->summary)<p>{{ $post->summary }}</p>@endif
            @include('admin.partials.blocks', ['blocks' => $post->blocks])
        </article>
    @endif
</div>
@endsection
