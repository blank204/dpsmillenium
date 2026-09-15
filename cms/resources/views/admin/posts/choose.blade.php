@extends('admin.layout')
@section('title', 'New post')
@section('content')
<div data-screen="choose-section">
    <h1>What are you posting?</h1>
    <ul data-region="section-choices">
        @foreach ($sections as $section)
            <li><a href="{{ route('admin.posts.create', $section->value) }}" data-section="{{ $section->value }}">{{ $section->label() }}</a></li>
        @endforeach
    </ul>
</div>
@endsection
