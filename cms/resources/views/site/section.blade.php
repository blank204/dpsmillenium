@extends('site.layout')
@section('title', $section->label())
@section('meta_description', 'All '.strtolower($section->label()).' from DPS Millennium, Doha.')
@section('content')
@php($blurbs = [
    'info' => 'Notices, timetables and anything else families need to know.',
    'event' => 'Reports and photographs from things that have already happened at school.',
    'circular' => 'Syllabus, examination and academic letters from the school office.',
    'newsletter' => 'Every issue of the school newsletter.',
])
<div class="wrap">
    <div class="page-head">
        <h1>{{ $section->label() }}</h1>
        <p>{{ $blurbs[$section->value] ?? '' }}</p>
    </div>

    @if ($posts->isEmpty())
        <div class="empty">
            <p>Nothing has been posted here yet.</p>
            <p>Please check back soon, or <a href="{{ route('archive') }}">look in the archive</a>.</p>
        </div>
    @else
        <div class="list">
            @foreach ($posts as $post)
                @php($scope = $post->gradeScope()->label())
                @php($cover = $post->cover_media_id ? \App\Models\MediaAsset::find($post->cover_media_id) : null)
                <article class="list-item">
                    <div class="{{ $cover ? 'card-media' : '' }}">
                        @if ($cover)
                            <a href="{{ $post->publicPath() }}"><img src="{{ $cover->url('400.jpg') }}" alt="" loading="lazy" width="400"></a>
                        @endif
                        <div>
                            @if ($post->isPinnedAt($now))<span class="pin">Pinned</span>@endif
                            @if ($scope)<span class="grade-tag">{{ $scope }}</span>@endif
                            <h2><a href="{{ $post->publicPath() }}">{{ $post->title }}</a></h2>
                            @if ($post->summary)<p>{{ $post->summary }}</p>@endif
                            <p class="meta">
                                @if ($post->section->value === 'event' && $post->event_date_start)
                                    Held {{ $post->event_date_start->format('j M Y') }}@if ($post->event_date_end && ! $post->event_date_end->isSameDay($post->event_date_start))–{{ $post->event_date_end->format('j M Y') }}@endif ·
                                @endif
                                Posted <time datetime="{{ $post->sort_at?->toDateString() }}">{{ $post->sort_at?->format('j M Y') }}</time>
                                @if ($post->updated_at_public && $post->first_published_at && $post->updated_at_public->gt($post->first_published_at))
                                    · Updated {{ $post->updated_at_public->format('j M Y') }}
                                @endif
                            </p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="pager">{{ $posts->links() }}</div>
    @endif
</div>
@endsection
