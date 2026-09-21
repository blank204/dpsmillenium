<?php

namespace App\Content;

use App\Models\Post;
use App\Models\Setting;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

final class HomepageBox
{
    /** @return Collection<int, Post> */
    public static function items(Section $section, ?CarbonInterface $now = null): Collection
    {
        $limit = max(1, (int) Setting::get('homepage_count.'.$section->value, 4));

        return Post::query()
            ->publicEligible($now)
            ->ofSection($section)
            ->orderedForDisplay($now)
            ->limit($limit)
            ->get();
    }
}
