<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\CalendarEntry;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function __invoke(Request $request)
    {
        $now = CarbonImmutable::now();
        $year = (int) $request->query('year', $now->year);
        $month = (int) $request->query('month', $now->month);
        abort_unless($year >= 2000 && $year <= 2100 && $month >= 1 && $month <= 12, 404);

        $start = CarbonImmutable::create($year, $month, 1);
        $end = $start->addMonth();

        return view('site.calendar', [
            'monthStart' => $start,
            'now' => $now,
            'entries' => CalendarEntry::with('type')->overlapping($start, $end)->orderBy('start_date')->get(),
            'upcoming' => CalendarEntry::with('type')
                ->where(fn ($q) => $q->whereDate('start_date', '>=', $now)->orWhereDate('end_date', '>=', $now))
                ->orderBy('start_date')
                ->limit(10)
                ->get(),
        ]);
    }
}
