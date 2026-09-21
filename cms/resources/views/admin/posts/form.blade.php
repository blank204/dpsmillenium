@extends('admin.layout')
@section('title', $post->exists ? 'Edit post' : 'New '.$post->section->label())
@section('content')
@php($status = $post->exists ? $post->adminStatus() : 'new')
@php($isLive = in_array($status, ['live', 'hidden'], true))
@php($dt = fn ($v) => $v ? $v->format('Y-m-d\TH:i') : '')
@php($statusLabels = ['new' => 'Not saved yet', 'draft' => 'Draft', 'scheduled' => 'Scheduled', 'live' => 'Live', 'hidden' => 'Removed from site'])
<div data-screen="post-editor" data-state="{{ $status }}" data-section="{{ $post->section->value }}">

    <h1 class="h1">{{ $post->exists ? 'Edit' : 'New' }}: {{ $post->section->label() }}</h1>

    <div class="editor-head" data-region="post-meta">
        <span class="badge badge-{{ $status === 'new' ? 'draft' : $status }}">{{ $statusLabels[$status] }}</span>
        @if ($post->exists)
            @if ($post->first_published_at)<span class="meta">Posted {{ $post->first_published_at->format('j M Y, g:i A') }}</span>@endif
            @if ($post->updated_at_public)<span class="meta">Updated {{ $post->updated_at_public->format('j M Y, g:i A') }}</span>@endif
            @if ($post->publicPath())
                <a class="meta" href="{{ url($post->publicPath()) }}" target="_blank" rel="noopener"><code>{{ $post->publicPath() }}</code> ↗</a>
            @endif
            @if (auth()->user()->isSuperAdmin())
                <a class="btn btn-sm" href="{{ route('admin.posts.versions', $post) }}">Version history</a>
            @endif
        @endif
    </div>

    @if ($status === 'scheduled' && $post->publish_at)
        <p class="notice notice-info">Scheduled to go live {{ $post->publish_at->format('j M Y, g:i A') }} ({{ $post->publish_at->diffForHumans() }}). Nobody outside school can see it until then.</p>
    @endif
    @if ($status === 'hidden')
        <p class="notice notice-warn">This post has passed its removal date, so it is no longer on the website. Clear the removal date below to bring it back.</p>
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

        <div class="editor-grid">
            <div class="editor-main">

                <fieldset data-region="basics">
                    <legend>Basics</legend>
                    <label class="field">
                        <span>Title</span>
                        <input name="title" required maxlength="{{ config('cms.limits.title') }}" value="{{ old('title', $post->title) }}">
                        <span class="hint">Up to {{ config('cms.limits.title') }} characters.</span>
                    </label>
                    <label class="field">
                        <span>Short summary (optional)</span>
                        <textarea name="summary" maxlength="{{ config('cms.limits.summary') }}" rows="2">{{ old('summary', $post->summary) }}</textarea>
                        <span class="hint">Shown in lists and WhatsApp previews.</span>
                    </label>
                </fieldset>

                <fieldset data-region="grades">
                    <legend>Grades (optional)</legend>
                    @php($mode = old('grades.mode', $post->grade_mode ?? 'none'))
                    @php($ids = collect(old('grades.ids', $post->grade_ids ?? []))->map(fn ($i) => (int) $i))
                    <div class="check-row">
                        @foreach (['none' => 'Not grade-specific', 'all' => 'All grades', 'range' => 'A range', 'specific' => 'Pick grades'] as $v => $l)
                            <label class="check"><input type="radio" name="grades[mode]" value="{{ $v }}" @checked($mode === $v)> {{ $l }}</label>
                        @endforeach
                    </div>
                    <div data-grade-mode="range" class="grid-2 mt">
                        <label class="field" style="margin-top:0">
                            <span>From</span>
                            <select name="grades[from]">@foreach ($grades as $g)<option value="{{ $g->id }}" @selected((int) old('grades.from', $ids->first()) === $g->id)>{{ $g->label }}</option>@endforeach</select>
                        </label>
                        <label class="field" style="margin-top:0">
                            <span>To</span>
                            <select name="grades[to]">@foreach ($grades as $g)<option value="{{ $g->id }}" @selected((int) old('grades.to', $ids->last()) === $g->id)>{{ $g->label }}</option>@endforeach</select>
                        </label>
                    </div>
                    <div data-grade-mode="specific" class="mt">
                        <span class="field-label">Tick the exact grades</span>
                        <div class="check-row">
                            @foreach ($grades as $g)<label class="check"><input type="checkbox" name="grades[ids][]" value="{{ $g->id }}" @checked($ids->contains($g->id))> {{ $g->label }}</label>@endforeach
                        </div>
                    </div>
                </fieldset>

                @if ($post->section->value === 'event')
                    <fieldset data-region="event-fields">
                        <legend>Event details</legend>
                        <div class="grid-2">
                            <label class="field" style="margin-top:0">
                                <span>Date the event happened</span>
                                <input type="date" name="event_date_start" value="{{ old('event_date_start', $post->event_date_start?->format('Y-m-d')) }}">
                            </label>
                            <label class="field" style="margin-top:0">
                                <span>End date (if it ran over several days)</span>
                                <input type="date" name="event_date_end" value="{{ old('event_date_end', $post->event_date_end?->format('Y-m-d')) }}">
                            </label>
                        </div>
                    </fieldset>
                @endif

                @if (in_array($post->section->value, ['event', 'newsletter'], true))
                    <fieldset data-region="cover">
                        <legend>Cover photo (required)</legend>
                        <input type="hidden" name="cover_media_id" value="{{ old('cover_media_id', $post->cover_media_id) }}" data-media-field="image">
                        <div data-media-preview="cover_media_id"></div>
                        <label class="field" style="margin-top:0">
                            <span>Choose a photo</span>
                            <input type="file" accept="image/jpeg,image/png,image/webp,image/heic" data-upload-for="cover_media_id" data-kind="image">
                            <span class="hint">JPG, PNG or WebP up to {{ (int) (config('cms.limits.image_bytes') / 1048576) }} MB.</span>
                        </label>
                    </fieldset>
                @endif

                @if ($post->section->value === 'newsletter')
                    <fieldset data-region="newsletter-fields">
                        <legend>Newsletter issue</legend>
                        @if ($series->isEmpty())
                            <p data-state="empty" class="notice notice-warn">No newsletters set up yet. Ask IT to add one in Settings.</p>
                        @endif
                        <div class="grid-2">
                            <label class="field" style="margin-top:0">
                                <span>Newsletter series</span>
                                <select name="newsletter_series_id"><option value="">Choose…</option>@foreach ($series as $s)<option value="{{ $s->id }}" @selected((int) old('newsletter_series_id', $post->newsletter_series_id) === $s->id)>{{ $s->name }}</option>@endforeach</select>
                            </label>
                            @php($year = old('newsletter_academic_year', $post->academic_year ?? \App\Content\AcademicYear::current()))
                            <label class="field" style="margin-top:0">
                                <span>Academic year</span>
                                <select name="newsletter_academic_year">@foreach (range((int) substr(\App\Content\AcademicYear::current(), 0, 4) + 1, 2026) as $y)@php($label = sprintf('%d-%02d', $y, ($y + 1) % 100))<option @selected($year === $label)>{{ $label }}</option>@endforeach</select>
                            </label>
                            <label class="field" style="margin-top:0">
                                <span>Term</span>
                                <input name="newsletter_term" maxlength="40" placeholder="Term I" value="{{ old('newsletter_term', $post->newsletter_term) }}">
                            </label>
                        </div>
                        <p class="hint mt">Add <strong>either</strong> a PDF <strong>or</strong> a link.</p>
                        <input type="hidden" name="newsletter_pdf_media_id" value="{{ old('newsletter_pdf_media_id', $post->newsletter_pdf_media_id) }}">
                        <div data-media-preview="newsletter_pdf_media_id"></div>
                        <div class="grid-2">
                            <label class="field">
                                <span>PDF</span>
                                <input type="file" accept="application/pdf" data-upload-for="newsletter_pdf_media_id" data-kind="pdf">
                            </label>
                            <label class="field">
                                <span>Link</span>
                                <input type="url" name="newsletter_link" value="{{ old('newsletter_link', $post->newsletter_link) }}" placeholder="https://">
                            </label>
                        </div>
                    </fieldset>
                @endif

                <fieldset data-region="blocks">
                    <legend>Content blocks</legend>
                    <div id="block-list" data-region="block-list"></div>
                    <div data-region="add-block" class="split mt">
                        <label class="field" style="margin-top:0;flex:1 1 14rem">
                            <span>Add a block</span>
                            <select id="add-block-type">
                                <option value="">Choose…</option>
                                <option value="text">Text</option>
                                <option value="image">Photo</option>
                                <option value="gallery">Photo gallery</option>
                                <option value="pdf">PDF file(s)</option>
                                <option value="link">Link button</option>
                                <option value="youtube">YouTube video</option>
                                <option value="video">Short video (MP4, max {{ (int) (config('cms.limits.video_bytes') / 1048576) }} MB)</option>
                                <option value="table">Table (paste from Excel)</option>
                                @if (auth()->user()->isSuperAdmin())<option value="html_page">Custom HTML page (IT only)</option>@endif
                            </select>
                        </label>
                        <button type="button" id="add-block" class="btn">+ Add block</button>
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
            </div>

            <aside class="editor-side">
                <section class="card" data-region="publish-dialog">
                    <h2>{{ $isLive ? 'Save changes' : 'Publishing' }}</h2>
                    @if (! $isLive)
                        @php($when = old('publish_when', $status === 'scheduled' ? 'schedule' : 'now'))
                        <div class="mt">
                            <label class="check"><input type="radio" name="publish_when" value="now" @checked($when === 'now')> Publish now</label>
                            <label class="check"><input type="radio" name="publish_when" value="schedule" @checked($when === 'schedule')> Schedule for later (Qatar time)</label>
                        </div>
                        <label class="field">
                            <span>Date and time</span>
                            <input type="datetime-local" name="publish_at" value="{{ old('publish_at', $dt($post->publish_at)) }}">
                        </label>
                    @else
                        <input type="hidden" name="publish_when" value="now">
                        <label class="check"><input type="checkbox" name="small_fix" value="1" @checked(old('small_fix'))> Small fix, don't move it to the top</label>
                        <p class="hint">Parents will see “Updated” with today’s date either way.</p>
                    @endif

                    <label class="field">
                        <span>Remove from the website on (optional)</span>
                        <input type="datetime-local" name="remove_on" value="{{ old('remove_on', $dt($post->remove_on)) }}">
                    </label>

                    <label class="check"><input type="checkbox" name="pin" value="1" @checked(old('pin', $post->pinned_until && $post->pinned_until->isFuture()))> Pin to the top of its box</label>
                    <label class="field">
                        <span>Unpin on (required when pinned)</span>
                        <input type="datetime-local" name="pinned_until" value="{{ old('pinned_until', $dt($post->pinned_until)) }}">
                    </label>

                    @include('admin.partials.actor-name')

                    <p class="btn-row mt" data-region="submit-actions">
                        <button type="submit" name="intent" value="publish" class="btn btn-primary">{{ $isLive ? 'Save changes' : ($status === 'scheduled' ? 'Update schedule' : 'Publish') }}</button>
                        @if (! $isLive && $status !== 'scheduled')
                            <button type="submit" name="intent" value="draft" formnovalidate class="btn">Save draft</button>
                        @endif
                        <button type="button" id="preview-button" class="btn" data-preview-url="{{ route('admin.posts.preview') }}">Preview</button>
                    </p>
                </section>
            </aside>
        </div>
    </form>

    @if ($status === 'scheduled')
        <form method="post" action="{{ route('admin.posts.unschedule', $post) }}" data-region="unschedule" class="card mt" style="max-width:24rem">
            @csrf
            <h2 class="eyebrow">Unschedule</h2>
            <p class="hint">Puts it back to a draft. Nothing is lost.</p>
            @include('admin.partials.actor-name')
            <p class="btn-row mt"><button class="btn">Unschedule (back to draft)</button></p>
        </form>
    @endif
</div>
@endsection
@push('scripts')<script src="{{ asset('admin/editor.js') }}" defer></script>@endpush
