@extends('admin.layout')
@section('title', 'Calendar')
@section('content')
@php($prev = $monthStart->subMonth())
@php($next = $monthStart->addMonth())
<div data-screen="calendar">
    <h1>Calendar: {{ $monthStart->format('F Y') }}</h1>
    <p data-region="month-nav">
        <a href="{{ route('admin.calendar.index', ['year' => $prev->year, 'month' => $prev->month]) }}">← {{ $prev->format('F') }}</a>
        <a href="{{ route('admin.calendar.index', ['year' => $next->year, 'month' => $next->month]) }}">{{ $next->format('F') }} →</a>
    </p>
    @include('admin.partials.errors')

    <table data-region="month-grid">
        <thead><tr>@foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $d)<th>{{ $d }}</th>@endforeach</tr></thead>
        <tbody>
        @php($day = $monthStart->startOfWeek(\Carbon\CarbonInterface::SUNDAY))
        @while ($day < $next->subDay()->endOfWeek(\Carbon\CarbonInterface::SATURDAY))
            <tr>
            @for ($i = 0; $i < 7; $i++)
                @php($dayEntries = $entries->filter(fn ($e) => $e->start_date->lte($day) && ($e->end_date ?? $e->start_date)->gte($day)))
                <td data-state="{{ $day->month === $monthStart->month ? 'in-month' : 'out-of-month' }}">
                    <span>{{ $day->day }}</span>
                    @foreach ($dayEntries as $e)<div data-type="{{ \Illuminate\Support\Str::slug($e->type->name) }}"><a href="{{ route('admin.calendar.index', ['year' => $monthStart->year, 'month' => $monthStart->month, 'edit' => $e->id]) }}">{{ $e->title }}</a></div>@endforeach
                </td>
                @php($day = $day->addDay())
            @endfor
            </tr>
        @endwhile
        </tbody>
    </table>

    @php($e = $editing)
    <form method="post" action="{{ $e ? route('admin.calendar.update', $e) : route('admin.calendar.store') }}" data-region="calendar-form">
        @csrf @if ($e) @method('PUT') @endif
        <h2>{{ $e ? 'Edit entry' : 'Add an entry' }}</h2>
        <label>Title <input name="title" required maxlength="150" value="{{ old('title', $e?->title) }}"></label>
        <label>Type <select name="calendar_type_id">@foreach ($types as $t)<option value="{{ $t->id }}" @selected((int) old('calendar_type_id', $e?->calendar_type_id) === $t->id)>{{ $t->name }}</option>@endforeach</select></label>
        <label>Start date <input type="date" name="start_date" required value="{{ old('start_date', $e?->start_date?->format('Y-m-d')) }}"></label>
        <label>End date (optional) <input type="date" name="end_date" value="{{ old('end_date', $e?->end_date?->format('Y-m-d')) }}"></label>
        <label>Grades <select name="grades[mode]"><option value="none">Not grade-specific</option><option value="all" @selected(old('grades.mode', $e?->grade_mode) === 'all')>All grades</option></select></label>
        <label>Note (optional) <textarea name="note" maxlength="500">{{ old('note', $e?->note) }}</textarea></label>
        @include('admin.partials.actor-name')
        <button>{{ $e ? 'Save changes' : 'Add to calendar' }}</button>
    </form>

    @if ($e)
        <form method="post" action="{{ route('admin.calendar.destroy', $e) }}" data-region="calendar-delete">@csrf @method('DELETE') <h2>Delete “{{ $e->title }}”</h2>@include('admin.partials.actor-name')<button>Delete</button></form>
    @endif
</div>
@endsection
