<?php

namespace App\Http\Controllers\Site;

use App\Content\Section;
use App\Http\Controllers\Controller;
use App\Models\CalendarEntry;
use App\Models\Post;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * The marketing front page. Its notice board and calendar widget are fed by the
     * CMS rather than written into the page, so the office can change what families
     * see without anyone editing HTML.
     */
    public function __invoke(Request $request)
    {
        $now = CarbonImmutable::now();

        $boxes = collect(Section::cases())->map(fn (Section $s) => [
            'section' => $s,
            'posts' => Post::query()
                ->ofSection($s)
                ->publicEligible($now)
                ->orderedForDisplay($now)
                ->limit(max(1, (int) Setting::get('homepage_count.'.$s->value, 5)))
                ->get(),
        ])->all();

        // The little month grid takes ?month=YYYY-MM so its arrows work without
        // JavaScript; anything unparseable just falls back to this month.
        $month = $now->startOfMonth();
        if (preg_match('/^(\d{4})-(\d{2})$/', (string) $request->query('month'), $m)
            && (int) $m[2] >= 1 && (int) $m[2] <= 12
            && (int) $m[1] >= 2000 && (int) $m[1] <= 2100) {
            $month = CarbonImmutable::create((int) $m[1], (int) $m[2], 1);
        }

        return view('site.home', [
            'now' => $now,
            'boxes' => $boxes,
            'monthStart' => $month,
            'monthEntries' => CalendarEntry::with('type')->overlapping($month, $month->addMonth())->get(),
            'upcoming' => CalendarEntry::with('type')
                ->where(fn ($q) => $q->whereDate('start_date', '>=', $now)->orWhereDate('end_date', '>=', $now))
                ->orderBy('start_date')
                ->limit(5)
                ->get(),
        ]);
    }
}
