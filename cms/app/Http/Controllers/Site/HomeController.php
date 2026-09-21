<?php

namespace App\Http\Controllers\Site;

use App\Content\Section;
use App\Http\Controllers\Controller;
use App\Models\CalendarEntry;
use App\Models\Post;
use App\Models\Setting;
use Carbon\CarbonImmutable;

class HomeController extends Controller
{
    public function __invoke()
    {
        $now = CarbonImmutable::now();

        $boxes = collect(Section::cases())->mapWithKeys(fn (Section $s) => [$s->value => [
            'section' => $s,
            'posts' => Post::query()
                ->ofSection($s)
                ->publicEligible($now)
                ->orderedForDisplay($now)
                ->limit(max(1, (int) Setting::get('homepage_count.'.$s->value, 5)))
                ->get(),
        ]])->all();

        return view('site.home', [
            'boxes' => $boxes,
            'now' => $now,
            'upcoming' => CalendarEntry::with('type')
                ->where(fn ($q) => $q->whereDate('start_date', '>=', $now)->orWhereDate('end_date', '>=', $now))
                ->orderBy('start_date')
                ->limit(6)
                ->get(),
        ]);
    }
}
