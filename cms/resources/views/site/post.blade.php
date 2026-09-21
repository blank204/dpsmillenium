@extends('site.layout')
@section('title', $post->title)
@section('meta_description', $post->summary ?: $post->section->label().' from DPS Millennium, Doha.')
@section('content')
@php($scope = $post->gradeScope()->label())
<div class="wrap">
    <div class="page-head">
        <p class="eyebrow"><a href="{{ route('section', $post->section->pathSegment()) }}">{{ $post->section->label() }}</a></p>
    </div>

    <article class="article public-article">
        @if ($scope)<span class="grade-tag">{{ $scope }}</span>@endif
        <h1 class="article-title">{{ $post->title }}</h1>
        @if ($post->summary)<p class="article-summary">{{ $post->summary }}</p>@endif

        <p class="meta" style="margin-top:.8rem">
            @if ($post->section->value === 'event' && $post->event_date_start)
                Held {{ $post->event_date_start->format('j F Y') }}@if ($post->event_date_end && ! $post->event_date_end->isSameDay($post->event_date_start))–{{ $post->event_date_end->format('j F Y') }}@endif ·
            @endif
            Posted <time datetime="{{ $post->first_published_at?->toDateString() }}">{{ ($post->first_published_at ?? $post->sort_at)?->format('j F Y') }}</time>
            @if ($post->updated_at_public && $post->first_published_at && $post->updated_at_public->gt($post->first_published_at))
                · Updated <time datetime="{{ $post->updated_at_public->toDateString() }}">{{ $post->updated_at_public->format('j F Y') }}</time>
            @endif
        </p>

        @if ($post->section->value === 'newsletter' && ($post->newsletter_term || $post->academic_year))
            <p class="meta">{{ collect([$post->newsletter_term, $post->academic_year])->filter()->implode(' · ') }}</p>
        @endif

        <div class="divider"></div>

        @if ($cover = $post->cover_media_id ? \App\Models\MediaAsset::find($post->cover_media_id) : null)
            <figure style="margin-bottom:1.2rem">
                <picture>
                    <source type="image/webp" srcset="{{ $cover->url('800.webp') }} 800w, {{ $cover->url('1600.webp') }} 1600w">
                    <img src="{{ $cover->url('800.jpg') }}" srcset="{{ $cover->url('800.jpg') }} 800w, {{ $cover->url('1600.jpg') }} 1600w" sizes="(max-width: 800px) 100vw, 800px" alt="" width="800">
                </picture>
            </figure>
        @endif

        @include('admin.partials.blocks', ['blocks' => $post->blocks])

        @if ($post->section->value === 'newsletter')
            @if ($pdf = $post->newsletter_pdf_media_id ? \App\Models\MediaAsset::find($post->newsletter_pdf_media_id) : null)
                <ul data-region="pdfs" style="margin-top:1.2rem">
                    <li><a href="{{ $pdf->url('file.pdf') }}" target="_blank" rel="noopener">Read this issue (PDF)</a> · <a href="{{ $pdf->url('file.pdf') }}?download=1">Download</a></li>
                </ul>
            @endif
            @if ($post->newsletter_link)
                <p style="margin-top:1.2rem"><a href="{{ $post->newsletter_link }}" target="_blank" rel="noopener" data-region="link-button">Read this issue ↗</a></p>
            @endif
        @endif
    </article>

    <p style="margin-top:1.4rem"><a href="{{ route('section', $post->section->pathSegment()) }}">← All {{ strtolower($post->section->label()) }}</a></p>
</div>
@endsection
