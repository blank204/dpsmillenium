<?php

namespace App\Http\Controllers\Site;

use App\Content\Section;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Carbon\CarbonImmutable;

class SectionController extends Controller
{
    public function __invoke(string $section)
    {
        $s = collect(Section::cases())->first(fn (Section $c) => $c->pathSegment() === $section);
        abort_if($s === null, 404);

        $now = CarbonImmutable::now();

        return view('site.section', [
            'section' => $s,
            'now' => $now,
            'posts' => Post::query()
                ->ofSection($s)
                ->publicEligible($now)
                ->orderedForDisplay($now)
                ->paginate(20),
        ]);
    }
}
