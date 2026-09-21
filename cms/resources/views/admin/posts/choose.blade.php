@extends('admin.layout')
@section('title', 'New post')
@section('content')
@php($blurbs = [
    'info' => 'Notices, timetables, anything parents need to know.',
    'event' => 'Something that already happened. Needs a date and a cover photo.',
    'circular' => 'Syllabus, exam and academic letters — usually a PDF.',
    'newsletter' => 'One issue of a series, as a PDF or a link.',
])
<div data-screen="choose-section">
    <h1 class="h1">What are you posting?</h1>
    <p class="lede">Pick one. You can change the wording later, not the box it sits in.</p>

    <div class="choose-grid" data-region="section-choices">
        @foreach ($sections as $i => $section)
            <a class="choose-card" href="{{ route('admin.posts.create', $section->value) }}" data-section="{{ $section->value }}">
                <span class="num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                <b>{{ $section->label() }}</b>
                <span>{{ $blurbs[$section->value] ?? '' }}</span>
            </a>
        @endforeach
    </div>
</div>
@endsection
