@extends('admin.layout')
@section('title', 'Calendar')
@section('content')
@php($prev = $monthStart->subMonth())
@php($next = $monthStart->addMonth())
@php($e = $editing)
@php($scope = $e ? $e->gradeScope() : \App\Content\GradeScope::fromStorage('none', []))
@php($mode = old('grades.mode', $scope->mode))
@php($ids = collect(old('grades.ids', $scope->ids))->map(fn ($i) => (int) $i))
@php($today = \Carbon\CarbonImmutable::now())
<div data-screen="calendar">
    <h1 class="h1">Holidays and exams</h1>
    <p class="lede">These show on the public calendar. They are not posts.</p>

    @include('admin.partials.errors')

    <div class="cal-grid">
        <section class="card">
            <div class="cal-head" data-region="month-nav">
                <a class="btn btn-sm" href="{{ route('admin.calendar.index', ['year' => $prev->year, 'month' => $prev->month]) }}">← {{ $prev->format('F') }}</a>
                <b>{{ $monthStart->format('F Y') }}</b>
                <a class="btn btn-sm" href="{{ route('admin.calendar.index', ['year' => $next->year, 'month' => $next->month]) }}">{{ $next->format('F') }} →</a>
            </div>

            <div class="cal-month" data-region="month-grid">
                @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d)<span class="dow">{{ $d }}</span>@endforeach
                @php($day = $monthStart->startOfWeek(\Carbon\CarbonInterface::SUNDAY))
                @php($gridEnd = $next->subDay()->endOfWeek(\Carbon\CarbonInterface::SATURDAY))
                @while ($day < $gridEnd)
                    @php($inMonth = $day->month === $monthStart->month)
                    @php($dayEntries = $entries->filter(fn ($x) => $x->start_date->lte($day) && ($x->end_date ?? $x->start_date)->gte($day)))
                    <div class="cal-cell {{ $inMonth ? '' : 'is-blank' }} {{ $day->isSameDay($today) ? 'is-today' : '' }}"
                         data-state="{{ $inMonth ? 'in-month' : 'out-of-month' }}">
                        <span class="cal-day">{{ $day->day }}</span>
                        @foreach ($dayEntries as $x)
                            <a class="cal-chip" data-type="{{ \Illuminate\Support\Str::slug($x->type->name) }}"
                               href="{{ route('admin.calendar.index', ['year' => $monthStart->year, 'month' => $monthStart->month, 'edit' => $x->id]) }}"
                               title="{{ $x->type->name }}: {{ $x->title }}">{{ $x->title }}</a>
                        @endforeach
                    </div>
                    @php($day = $day->addDay())
                @endwhile
            </div>

            @if ($entries->isEmpty())
                <p class="meta mt">No entries this month. Add one on the right.</p>
            @endif
        </section>

        <aside class="card" data-region="calendar-form">
            <h2 class="eyebrow">{{ $e ? 'Edit entry' : 'Add an entry' }}</h2>
            <form method="post" action="{{ $e ? route('admin.calendar.update', $e) : route('admin.calendar.store') }}">
                @csrf @if ($e) @method('PUT') @endif
                <label class="field">
                    <span>Title</span>
                    <input name="title" required maxlength="{{ config('cms.limits.title') }}" value="{{ old('title', $e?->title) }}">
                </label>
                <label class="field">
                    <span>Type</span>
                    <select name="calendar_type_id">@foreach ($types as $t)<option value="{{ $t->id }}" @selected((int) old('calendar_type_id', $e?->calendar_type_id) === $t->id)>{{ $t->name }}</option>@endforeach</select>
                </label>
                <div class="grid-2">
                    <label class="field">
                        <span>Start date</span>
                        <input type="date" name="start_date" required value="{{ old('start_date', $e?->start_date?->format('Y-m-d')) }}">
                    </label>
                    <label class="field">
                        <span>End date (optional)</span>
                        <input type="date" name="end_date" value="{{ old('end_date', $e?->end_date?->format('Y-m-d')) }}">
                    </label>
                </div>

                <div class="field">
                    <span class="field-label">Grades (optional)</span>
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
                    <div data-grade-mode="specific" class="check-row">
                        @foreach ($grades as $g)<label class="check"><input type="checkbox" name="grades[ids][]" value="{{ $g->id }}" @checked($ids->contains($g->id))> {{ $g->label }}</label>@endforeach
                    </div>
                </div>

                <label class="field">
                    <span>Note (optional)</span>
                    <textarea name="note" rows="2" maxlength="{{ config('cms.limits.calendar_note') }}">{{ old('note', $e?->note) }}</textarea>
                </label>
                @include('admin.partials.actor-name')
                <p class="btn-row mt"><button class="btn btn-primary">{{ $e ? 'Save changes' : 'Add to calendar' }}</button></p>
            </form>

            @if ($e)
                <div class="divider"></div>
                <form method="post" action="{{ route('admin.calendar.destroy', $e) }}" data-region="calendar-delete">
                    @csrf @method('DELETE')
                    <h2 class="eyebrow">Delete “{{ $e->title }}”</h2>
                    @include('admin.partials.actor-name')
                    <p class="btn-row mt"><button class="btn btn-sm btn-danger">Delete this entry</button></p>
                </form>
                <p class="mt"><a href="{{ route('admin.calendar.index', ['year' => $monthStart->year, 'month' => $monthStart->month]) }}">Cancel editing</a></p>
            @endif
        </aside>
    </div>
</div>
@endsection
@push('scripts')<script src="{{ asset('admin/grade-picker.js') }}" defer></script>@endpush
