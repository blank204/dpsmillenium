<?php

namespace App\Http\Controllers\Site;

use App\Content\AcademicYear;
use App\Content\Section;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArchiveController extends Controller
{
    /**
     * One archive for all four sections, as agreed: filter by section, school year and
     * month, plus a full-text search over titles and body text. Posts still showing in a
     * homepage box are included; posts past their removal date are not, because
     * publicEligible() already drops them.
     */
    public function __invoke(Request $request)
    {
        $now = CarbonImmutable::now();
        $query = Post::query()->publicEligible($now);

        $section = null;
        if ($request->filled('section')) {
            $section = collect(Section::cases())->first(fn (Section $c) => $c->value === $request->query('section'));
            if ($section !== null) {
                $query->ofSection($section);
            }
        }

        $year = trim((string) $request->query('year'));
        if ($year !== '' && preg_match('/^\d{4}-\d{2}$/', $year)) {
            [$start, $end] = AcademicYear::bounds($year);
            $query->where('sort_at', '>=', $start)->where('sort_at', '<', $end);
        } else {
            $year = '';
        }

        $month = (int) $request->query('month');
        if ($month >= 1 && $month <= 12) {
            $query->whereMonth('sort_at', $month);
        } else {
            $month = 0;
        }

        $q = trim((string) $request->query('q'));
        if ($q !== '') {
            $this->search($query, $q);
        }

        return view('site.archive', [
            'posts' => $query->orderByDesc('sort_at')->orderByDesc('id')->paginate(25)->withQueryString(),
            'now' => $now,
            'section' => $section,
            'year' => $year,
            'month' => $month,
            'q' => $q,
            'years' => $this->years(),
        ]);
    }

    /**
     * MySQL's full-text index on (title, search_text) is what makes this fast on a
     * decade of posts. Very short words fall under MySQL's minimum token length and a
     * boolean-mode search for them finds nothing, so those fall back to LIKE.
     */
    private function search($query, string $q): void
    {
        $driver = DB::connection()->getDriverName();
        $longEnough = collect(preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY))
            ->contains(fn ($word) => mb_strlen($word) >= 4);

        if ($driver === 'mysql' && $longEnough) {
            $query->whereFullText(['title', 'search_text'], $q);

            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
        $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('search_text', 'like', $like));
    }

    /** School years that actually have something in them, newest first. */
    private function years(): array
    {
        $bounds = Post::query()->publicEligible()->selectRaw('MIN(sort_at) as lo, MAX(sort_at) as hi')->first();
        if ($bounds?->lo === null) {
            return [];
        }

        $labels = [];
        $cursor = CarbonImmutable::parse($bounds->lo);
        $last = CarbonImmutable::parse($bounds->hi);
        while ($cursor->lessThanOrEqualTo($last)) {
            $labels[AcademicYear::forDate($cursor)] = true;
            $cursor = $cursor->addMonth();
        }
        $labels[AcademicYear::forDate($last)] = true;

        return array_reverse(array_keys($labels));
    }
}
