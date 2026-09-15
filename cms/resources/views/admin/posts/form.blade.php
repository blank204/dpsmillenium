@extends('admin.layout')
@section('title', $post->exists ? 'Edit post' : 'New '.$post->section->label())
@section('content')
@php($status = $post->exists ? $post->adminStatus() : 'new')
@php($isLive = in_array($status, ['live', 'hidden'], true))
@php($dt = fn ($v) => $v ? $v->format('Y-m-d\TH:i') : '')
<div data-screen="post-editor" data-state="{{ $status }}" data-section="{{ $post->section->value }}">
    <h1>{{ $post->exists ? 'Edit' : 'New' }}: {{ $post->section->label() }}</h1>
    @if ($post->exists)
        <p data-region="post-meta">Status: {{ ucfirst($status) }}
            @if ($post->first_published_at) · Posted {{ $post->first_published_at->format('j M Y, g:i A') }}@endif
            @if ($post->updated_at_public) · Updated {{ $post->updated_at_public->format('j M Y, g:i A') }}@endif
            @if ($post->publicPath()) · Link: <code>{{ $post->publicPath() }}</code>@endif
            @if (auth()->user()->isSuperAdmin()) · <a href="{{ route('admin.posts.versions', $post) }}">Version history</a>@endif
        </p>
    @endif
    @include('admin.partials.errors')

    <form method="post" id="post-form"
          action="{{ $post->exists ? route('admin.posts.update', $post) : route('admin.posts.store', $post->section->value) }}"
          data-upload-url="{{ route('admin.media.upload') }}"
          data-media-edit-url="{{ url(config('cms.admin_path').'/media/__ID__/edit') }}"
          data-autosave-key="{{ $autosaveKey }}"
          data-updated-at="{{ $post->updated_at?->timestamp ?? 0 }}"
          data-super-admin="{{ auth()->user()->isSuperAdmin() ? '1' : '0' }}">
        @csrf
        @if ($post->exists) @method('PUT') @endif
        <input type="hidden" name="section" value="{{ $post->section->value }}">
        <input type="hidden" name="post_id" value="{{ $post->id }}">

        <fieldset data-region="basics"><legend>Basics</legend>
            <label>Title <input name="title" required maxlength="{{ config('cms.limits.title') }}" value="{{ old('title', $post->title) }}"></label>
            <label>Short summary (optional, shown in lists and WhatsApp previews)
                <textarea name="summary" maxlength="{{ config('cms.limits.summary') }}" rows="2">{{ old('summary', $post->summary) }}</textarea></label>
        </fieldset>

        <fieldset data-region="grades"><legend>Grades (optional)</legend>
            @php($mode = old('grades.mode', $post->grade_mode ?? 'none'))
            @php($ids = collect(old('grades.ids', $post->grade_ids ?? []))->map(fn ($i) => (int) $i))
            @foreach (['none' => 'Not grade-specific', 'all' => 'All grades', 'range' => 'A range', 'specific' => 'Pick grades'] as $v => $l)
                <label><input type="radio" name="grades[mode]" value="{{ $v }}" @checked($mode === $v)> {{ $l }}</label>
            @endforeach
            <div data-grade-mode="range">
                <label>From <select name="grades[from]">@foreach ($grades as $g)<option value="{{ $g->id }}" @selected((int) old('grades.from', $ids->first()) === $g->id)>{{ $g->label }}</option>@endforeach</select></label>
                <label>To <select name="grades[to]">@foreach ($grades as $g)<option value="{{ $g->id }}" @selected((int) old('grades.to', $ids->last()) === $g->id)>{{ $g->label }}</option>@endforeach</select></label>
            </div>
            <div data-grade-mode="specific">
                @foreach ($grades as $g)<label><input type="checkbox" name="grades[ids][]" value="{{ $g->id }}" @checked($ids->contains($g->id))> {{ $g->label }}</label>@endforeach
            </div>
        </fieldset>

        @if ($post->section->value === 'event')
            <fieldset data-region="event-fields"><legend>Event details</legend>
                <label>Date the event happened <input type="date" name="event_date_start" value="{{ old('event_date_start', $post->event_date_start?->format('Y-m-d')) }}"></label>
                <label>End date (if it ran over several days) <input type="date" name="event_date_end" value="{{ old('event_date_end', $post->event_date_end?->format('Y-m-d')) }}"></label>
            </fieldset>
        @endif

        @if (in_array($post->section->value, ['event', 'newsletter'], true))
            <fieldset data-region="cover"><legend>Cover photo (required)</legend>
                <input type="hidden" name="cover_media_id" value="{{ old('cover_media_id', $post->cover_media_id) }}" data-media-field="image">
                <div data-media-preview="cover_media_id"></div>
                <input type="file" accept="image/jpeg,image/png,image/webp,image/heic" data-upload-for="cover_media_id" data-kind="image">
            </fieldset>
        @endif

        @if ($post->section->value === 'newsletter')
            <fieldset data-region="newsletter-fields"><legend>Newsletter issue</legend>
                <label>Newsletter <select name="newsletter_series_id"><option value="">Choose…</option>@foreach ($series as $s)<option value="{{ $s->id }}" @selected((int) old('newsletter_series_id', $post->newsletter_series_id) === $s->id)>{{ $s->name }}</option>@endforeach</select></label>
                @if ($series->isEmpty())<p data-state="empty">No newsletters set up yet. Ask IT to add one in Settings.</p>@endif
                @php($year = old('newsletter_academic_year', $post->academic_year ?? \App\Content\AcademicYear::current()))
                <label>Academic year <select name="newsletter_academic_year">@foreach (range((int) substr(\App\Content\AcademicYear::current(), 0, 4) + 1, 2026) as $y)@php($label = sprintf('%d-%02d', $y, ($y + 1) % 100))<option @selected($year === $label)>{{ $label }}</option>@endforeach</select></label>
                <label>Term <input name="newsletter_term" maxlength="40" placeholder="Term I" value="{{ old('newsletter_term', $post->newsletter_term) }}"></label>
                <p>Add <strong>either</strong> a PDF <strong>or</strong> a link:</p>
                <input type="hidden" name="newsletter_pdf_media_id" value="{{ old('newsletter_pdf_media_id', $post->newsletter_pdf_media_id) }}">
                <div data-media-preview="newsletter_pdf_media_id"></div>
                <label>PDF <input type="file" accept="application/pdf" data-upload-for="newsletter_pdf_media_id" data-kind="pdf"></label>
                <label>Link <input type="url" name="newsletter_link" value="{{ old('newsletter_link', $post->newsletter_link) }}" placeholder="https://"></label>
            </fieldset>
        @endif

        <fieldset data-region="blocks"><legend>Content</legend>
            <div id="block-list" data-region="block-list"></div>
            <div data-region="add-block">
                <label>Add a block <select id="add-block-type">
                    <option value="">Choose…</option>
                    <option value="text">Text</option><option value="image">Photo</option><option value="gallery">Photo gallery</option>
                    <option value="pdf">PDF file(s)</option><option value="link">Link button</option><option value="youtube">YouTube video</option>
                    <option value="video">Short video (MP4, max 50 MB)</option><option value="table">Table (paste from Excel)</option>
                    @if (auth()->user()->isSuperAdmin())<option value="html_page">Custom HTML page (IT only)</option>@endif
                </select></label>
                <button type="button" id="add-block">Add</button>
            </div>
            <input type="hidden" name="blocks_json" id="blocks_json">
            {{-- Blade's @json() directive splits its argument on every literal comma (it is
                 not paren/bracket-depth aware) and only keeps the first three pieces as
                 value/flags/depth. With the ternary's one comma (inside json_decode(...,
                 true)), the literal "512" default depth ends up passed as the *flags*
                 argument instead, silently dropping the JSON_HEX_TAG/APOS/AMP/QUOT flags
                 @json() is supposed to apply — so a saved value containing "<!--<script>"
                 would render unescaped and break out of this script tag. Build the value in
                 a plain variable first, then pass a single, comma-free argument to @json(). --}}
            @php($blocksData = old('blocks_json') ? json_decode(old('blocks_json'), true) : ($post->blocks ?? []))
            <script type="application/json" id="blocks-data">@json($blocksData)</script>
            @php($mediaForBlocks = \App\Models\MediaAsset::whereIn('id', collect($post->blocks ?? [])->flatMap(fn ($b) => array_filter([$b['media_id'] ?? null, ...array_column($b['items'] ?? [], 'media_id'), ...array_column($b['files'] ?? [], 'media_id')]))->merge([$post->cover_media_id, $post->newsletter_pdf_media_id])->filter()->all())->get()->mapWithKeys(fn ($m) => [$m->id => ['id' => $m->id, 'kind' => $m->kind, 'original_name' => $m->original_name, 'preview_url' => $m->kind === 'image' ? $m->url('400.jpg') : $m->url($m->kind === 'pdf' ? 'file.pdf' : 'file.mp4')]]))
            <script type="application/json" id="media-data">@json($mediaForBlocks)</script>
            <p data-region="autosave-status" aria-live="polite"></p>
        </fieldset>

        <fieldset data-region="publish-dialog"><legend>{{ $isLive ? 'Save changes' : 'Publish' }}</legend>
            @if (! $isLive)
                <label><input type="radio" name="publish_when" value="now" @checked(old('publish_when', $status === 'scheduled' ? 'schedule' : 'now') === 'now')> Publish now</label>
                <label><input type="radio" name="publish_when" value="schedule" @checked(old('publish_when', $status === 'scheduled' ? 'schedule' : 'now') === 'schedule')> Schedule for later (Qatar time)</label>
                <label>Date and time <input type="datetime-local" name="publish_at" value="{{ old('publish_at', $dt($post->publish_at)) }}"></label>
            @else
                <input type="hidden" name="publish_when" value="now">
                <label><input type="checkbox" name="small_fix" value="1" @checked(old('small_fix'))> Small fix, don't move it to the top</label>
                <p>Parents will see “Updated” with today’s date either way.</p>
            @endif
            <label>Remove from the website on (optional) <input type="datetime-local" name="remove_on" value="{{ old('remove_on', $dt($post->remove_on)) }}"></label>
            <label><input type="checkbox" name="pin" value="1" @checked(old('pin', $post->pinned_until && $post->pinned_until->isFuture()))> Pin to the top of its box</label>
            <label>Unpin on (required when pinned) <input type="datetime-local" name="pinned_until" value="{{ old('pinned_until', $dt($post->pinned_until)) }}"></label>
            @include('admin.partials.actor-name')
            <p data-region="submit-actions">
                @if (! $isLive && $status !== 'scheduled')<button type="submit" name="intent" value="draft" formnovalidate>Save draft</button>@endif
                <button type="button" id="preview-button" data-preview-url="{{ route('admin.posts.preview') }}">Preview</button>
                <button type="submit" name="intent" value="publish">{{ $isLive ? 'Save changes' : ($status === 'scheduled' ? 'Update schedule' : 'Publish') }}</button>
            </p>
        </fieldset>
    </form>

    @if ($status === 'scheduled')
        <form method="post" action="{{ route('admin.posts.unschedule', $post) }}" data-region="unschedule">@csrf <h2>Unschedule</h2>@include('admin.partials.actor-name')<button>Unschedule (back to draft)</button></form>
    @endif
</div>
@endsection
@push('scripts')<script src="{{ asset('admin/editor.js') }}" defer></script>@endpush
