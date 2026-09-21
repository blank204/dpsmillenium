<?php

namespace App\Http\Controllers\Site;

use App\Content\Section;
use App\Http\Controllers\Controller;
use App\Models\Post;
use Carbon\CarbonImmutable;

class PostController extends Controller
{
    /**
     * A post's own shareable page. The slug in the URL is decoration — the id is what
     * finds the row — so a renamed post redirects to its new address rather than 404ing
     * on links parents have already shared.
     */
    public function __invoke(string $section, int $id, string $slug)
    {
        $s = collect(Section::cases())->first(fn (Section $c) => $c->pathSegment() === $section);
        abort_if($s === null, 404);

        $now = CarbonImmutable::now();
        $post = Post::query()->publicEligible($now)->whereKey($id)->first();

        abort_if($post === null || $post->section !== $s, 404);

        if ($post->slug !== $slug) {
            return redirect($post->publicPath(), 301);
        }

        return view('site.post', ['post' => $post, 'now' => $now]);
    }
}
