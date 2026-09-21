@extends('site.layout')
@section('title', 'Archive')
@section('meta_description', 'Search every notice, circular, event report and newsletter DPS Millennium has published.')
@section('content')
<div class="wrap">
    <div class="page-head">
        <h1>Archive</h1>
        <p>Everything the school has published, in one place. Narrow it down, or search for a word in the title or the text.</p>
    </div>

    <form method="get" class="filters" role="search">
        <label>
            <span>Section</span>
            <select name="section">
                <option value="">All sections</option>
                @foreach (\App\Content\Section::cases() as $s)
                    <option value="{{ $s->value }}" @selected($section?->value === $s->value)>{{ $s->label() }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span>School year</span>
            <select name="year">
                <option value="">All years</option>
                @foreach ($years as $y)
                    <option value="{{ $y }}" @selected($year === $y)>{{ $y }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span>Month</span>
            <select name="month">
                <option value="">All months</option>
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" @selected($month === $m)>{{ \Carbon\Carbon::create(2000, $m, 1)->format('F') }}</option>
                @endforeach
            </select>
        </label>
        <label style="flex:2 1 14rem">
            <span>Search</span>
            <input type="search" name="q" value="{{ $q }}" placeholder="For example: syllabus">
        </label>
        <button class="btn btn-primary">Search</button>
        @if ($section || $year || $month || $q !== '')
            <a class="btn" href="{{ route('archive') }}">Clear</a>
        @endif
    </form>

    @if ($posts->isEmpty())
        <div class="empty">
            <p>Nothing matches what you asked for.</p>
            <p>Try a different word, or choose “All years” and “All sections”.</p>
        </div>
    @else
        <p class="meta" style="margin-top:1.2rem">{{ number_format($posts->total()) }} {{ \Illuminate\Support\Str::plural('result', $posts->total()) }}@if ($q !== '') for “{{ $q }}”@endif.</p>

        <div class="list">
            @foreach ($posts as $post)
                @php($scope = $post->gradeScope()->label())
                <article class="list-item">
                    <p class="eyebrow">{{ $post->section->label() }}</p>
                    <h2><a href="{{ $post->publicPath() }}">{{ $post->title }}</a></h2>
                    @if ($post->summary)<p>{{ $post->summary }}</p>@endif
                    <p class="meta">
                        @if ($scope)<span class="grade-tag">{{ $scope }}</span> · @endif
                        <time datetime="{{ $post->sort_at?->toDateString() }}">{{ $post->sort_at?->format('j M Y') }}</time>
                    </p>
                </article>
            @endforeach
        </div>

        <div class="pager">{{ $posts->links() }}</div>
    @endif
</div>
@endsection
