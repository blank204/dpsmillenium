<?php

namespace App\Http\Controllers\Admin;

use App\Content\CalendarService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CalendarEntryRequest;
use App\Http\Requests\Admin\NameOnlyRequest;
use App\Models\CalendarEntry;
use App\Models\CalendarType;
use App\Models\Grade;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function __construct(private readonly CalendarService $calendar) {}

    public function index(Request $request)
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);
        abort_unless($year >= 2000 && $year <= 2100 && $month >= 1 && $month <= 12, 404);

        $start = CarbonImmutable::create($year, $month, 1);
        $end = $start->addMonth();

        return view('admin.calendar.index', [
            'monthStart' => $start,
            'entries' => CalendarEntry::with('type')->overlapping($start, $end)->orderBy('start_date')->get(),
            'types' => CalendarType::visible()->orderBy('id')->get(),
            'grades' => Grade::ordered()->visible()->get(),
            'editing' => $request->query('edit') ? CalendarEntry::find((int) $request->query('edit')) : null,
        ]);
    }

    public function store(CalendarEntryRequest $request)
    {
        $entry = $this->calendar->create($request->entryData(), $request->actor());

        return redirect()->route('admin.calendar.index', ['year' => $entry->start_date->year, 'month' => $entry->start_date->month])->with('status', 'Added to the calendar.');
    }

    public function update(CalendarEntryRequest $request, CalendarEntry $entry)
    {
        $entry = $this->calendar->update($entry, $request->entryData(), $request->actor());

        return redirect()->route('admin.calendar.index', ['year' => $entry->start_date->year, 'month' => $entry->start_date->month])->with('status', 'Calendar entry updated.');
    }

    public function destroy(NameOnlyRequest $request, CalendarEntry $entry)
    {
        $this->calendar->delete($entry, $request->actor());

        return back()->with('status', 'Calendar entry deleted.');
    }
}
