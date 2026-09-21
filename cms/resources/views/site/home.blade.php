@extends('site.layout')
@section('title', 'DPS Millennium')
@section('meta_description', 'Notices, academic circulars, events and the school calendar for DPS Millennium, Doha.')
@section('content')
<div class="wrap">
    <div class="page-head">
        <h1>What’s happening at school</h1>
        <p>Notices, circulars, event reports and the calendar — all kept up to date by the school office.</p>
    </div>

    <div class="boxes">
        @foreach ($boxes as $box)
            @php($section = $box['section'])
            <section class="box">
                <h2><a href="{{ route('section', $section->pathSegment()) }}">{{ $section->label() }}</a></h2>
                <div class="box-list">
                    @forelse ($box['posts'] as $post)
                        @include('site.partials.item', ['post' => $post, 'now' => $now])
                    @empty
                        <p class="meta">Nothing here just yet.</p>
                    @endforelse
                </div>
                <p class="box-more"><a href="{{ route('section', $section->pathSegment()) }}">See all {{ strtolower($section->label()) }} →</a></p>
            </section>
        @endforeach
    </div>

    <section class="box" style="margin-top:1.2rem">
        <h2><a href="{{ route('calendar') }}">School calendar</a></h2>
        @if ($upcoming->isEmpty())
            <p class="meta" style="margin-top:.8rem">Nothing on the calendar yet.</p>
        @else
            <div class="cal-upcoming">
                @foreach ($upcoming as $entry)
                    <div>
                        <span class="when">{{ $entry->start_date->format('j M') }}@if ($entry->end_date && ! $entry->end_date->isSameDay($entry->start_date))–{{ $entry->end_date->format('j M') }}@endif</span>
                        <span>{{ $entry->title }} <span class="meta">· {{ $entry->type->name }}</span></span>
                    </div>
                @endforeach
            </div>
        @endif
        <p class="box-more"><a href="{{ route('calendar') }}">See the full calendar →</a></p>
    </section>

    <p style="margin-top:1.4rem"><a href="{{ route('archive') }}">Looking for something older? Search the archive →</a></p>
</div>
@endsection
