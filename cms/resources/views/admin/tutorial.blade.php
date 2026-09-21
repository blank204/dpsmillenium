@extends('admin.layout')
@section('title', 'How to post')
@section('content')
@php($steps = [
    ['head' => 'Press “New post”', 'body' => 'It is the gold button on the Dashboard, and the second item in the menu on the left. Nothing is saved or shown to anyone until you press Publish at the end.'],
    ['head' => 'Pick the box it goes in', 'body' => 'Academic circulars for syllabus and exam letters. Information corner for notices and timetables. Events corner for something that already happened. Newsletter for one issue of a series. You can change the wording later, but not the box.'],
    ['head' => 'Type a title parents will recognise', 'body' => 'Write it the way a parent would read it — “Grade V Half Yearly Examination Syllabus 2026-27”, not “syllabus final v3”. The short summary underneath is what shows in lists and in WhatsApp previews.'],
    ['head' => 'Say which grades it is for', 'body' => 'Leave it on “Not grade-specific” if it is for everyone. Otherwise pick a range (Grades I–V) or tick the exact grades. Parents see this as a small label on the post.'],
    ['head' => 'Add the content', 'body' => 'Press “+ Add block” and choose what you need: Text for a message, PDF file(s) for a letter, Photo or Photo gallery for pictures, Table if you are pasting from Excel. Add as many blocks as you like and use the ↑ ↓ buttons to reorder them.'],
    ['head' => 'Type your name and press Publish', 'body' => 'The name box is on the right, and it starts blank every time on purpose — it records who made this change in the log. Press Preview first if you want to see the parent view. Then Publish.'],
])
<div data-screen="tutorial">
    <h1 class="h1">How to post a circular</h1>
    <p class="lede">Six steps. The same six work for every kind of post — only step 2 changes.</p>

    <div class="steps">
        @foreach ($steps as $i => $step)
            <div class="step">
                <span class="step-num">{{ $i + 1 }}</span>
                <div>
                    <b>{{ $step['head'] }}</b>
                    <p>{{ $step['body'] }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <p class="btn-row mt-lg">
        <a class="btn btn-primary btn-lg" href="{{ route('admin.posts.choose') }}">Start a post now</a>
        <a class="btn btn-lg" href="{{ route('admin.dashboard') }}">Back to the dashboard</a>
    </p>

    <div class="cards-2">
        <section class="card">
            <h2 class="eyebrow">Things worth knowing</h2>
            <div class="card-list mt">
                <div><b class="row-title">Save draft keeps it private</b><p class="meta">A draft is never visible to parents. Publish is the only thing that puts it on the website.</p></div>
                <div><b class="row-title">You can schedule it</b><p class="meta">Choose “Schedule for later” and pick a date and time in Qatar time. It goes live on its own.</p></div>
                <div><b class="row-title">Pinning needs an end date</b><p class="meta">If you pin a post to the top of its box, you must say when it should unpin. This stops old notices sitting at the top forever.</p></div>
                <div><b class="row-title">Editing republishes</b><p class="meta">Editing a live post moves it back to the top and shows an “Updated” date. Tick “Small fix” if it is only a typo and should stay where it is.</p></div>
                <div><b class="row-title">Deleting is not final</b><p class="meta">Deleted posts sit in the recycle bin for {{ config('cms.recycle_days') }} days. Only school IT can bring one back.</p></div>
            </div>
        </section>

        <section class="card">
            <h2 class="eyebrow">If something goes wrong</h2>
            <div class="card-list mt">
                <div><b class="row-title">A photo will not upload</b><p class="meta">Very large photos are refused. On a phone, share the picture at a smaller size and try again.</p></div>
                <div><b class="row-title">It says a field needs fixing</b><p class="meta">The red box at the top lists exactly what to change. Nothing is lost while you fix it.</p></div>
                <div><b class="row-title">You closed the tab by accident</b><p class="meta">Your typing is kept on that same device and browser. Reopen the post and it offers what you had.</p></div>
                <div><b class="row-title">Anything else</b><p class="meta">Contact school IT. Every action is recorded in the change log, so they can see what happened.</p></div>
            </div>
        </section>
    </div>
</div>
@endsection
