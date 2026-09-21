@extends('site.layout')
@section('title', 'School calendar')
@section('meta_description', 'Holidays, examinations and other dates for the DPS Millennium school year.')
@section('content')
@php($prev = $monthStart->subMonth())
@php($next = $monthStart->addMonth())
<div class="wrap">
    <div class="page-head">
        <h1>School calendar</h1>
        <p>Holidays, examinations and other dates families need. Times are Qatar time.</p>
    </div>

    <div class="cal-head">
        <a class="btn" href="{{ route('calendar', ['year' => $prev->year, 'month' => $prev->month]) }}">← {{ $prev->format('F Y') }}</a>
        <b>{{ $monthStart->format('F Y') }}</b>
        <a class="btn" href="{{ route('calendar', ['year' => $next->year, 'month' => $next->month]) }}">{{ $next->format('F Y') }} →</a>
    </div>

    <div class="cal-month">
        @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d)<span class="dow">{{ $d }}</span>@endforeach
        @php($day = $monthStart->startOfWeek(\Carbon\CarbonInterface::SUNDAY))
        @php($gridEnd = $next->subDay()->endOfWeek(\Carbon\CarbonInterface::SATURDAY))
        @while ($day < $gridEnd)
            @php($inMonth = $day->month === $monthStart->month)
            @php($dayEntries = $entries->filter(fn ($x) => $x->start_date->lte($day) && ($x->end_date ?? $x->start_date)->gte($day)))
            <div class="cal-cell {{ $inMonth ? '' : 'is-blank' }} {{ $day->isSameDay($now) ? 'is-today' : '' }}">
                <span class="cal-day">{{ $day->day }}</span>
                @foreach ($dayEntries as $x)
                    <span class="cal-entry {{ \Illuminate\Support\Str::contains(strtolower($x->type->name), 'exam') ? 'is-exam' : '' }}"
                          title="{{ $x->type->name }}: {{ $x->title }}">{{ $x->title }}</span>
                @endforeach
            </div>
            @php($day = $day->addDay())
        @endwhile
    </div>

    @if ($entries->isEmpty())
        <p class="meta" style="margin-top:1rem">Nothing is on the calendar for {{ $monthStart->format('F Y') }}.</p>
    @endif

    <section style="margin-top:2rem">
        <h2 class="eyebrow">Coming up</h2>
        @if ($upcoming->isEmpty())
            <p class="meta" style="margin-top:.8rem">Nothing scheduled yet.</p>
        @else
            <div class="cal-upcoming">
                @foreach ($upcoming as $entry)
                    <div>
                        <span class="when">{{ $entry->start_date->format('j M') }}@if ($entry->end_date && ! $entry->end_date->isSameDay($entry->start_date))–{{ $entry->end_date->format('j M') }}@endif</span>
                        <span>
                            {{ $entry->title }}
                            <span class="meta">· {{ $entry->type->name }}@if ($label = $entry->gradeScope()->label()) · {{ $label }}@endif</span>
                            @if ($entry->note)<br><span class="meta">{{ $entry->note }}</span>@endif
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
@endsection
