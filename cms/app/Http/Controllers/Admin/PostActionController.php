<?php

namespace App\Http\Controllers\Admin;

use App\Content\PostService;
use App\Content\PublishOptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NameOnlyRequest;
use App\Models\Post;

class PostActionController extends Controller
{
    public function __construct(private readonly PostService $posts) {}

    public function duplicate(NameOnlyRequest $request, Post $post)
    {
        $data = [
            'title' => mb_substr('Copy of '.$post->title, 0, config('cms.limits.title')),
            'summary' => $post->summary,
            'blocks' => $post->blocks,
            // A stored range is an expanded id list, so re-submit it as "specific" (same label).
            'grades' => ['mode' => $post->grade_mode === 'range' ? 'specific' : $post->grade_mode, 'ids' => $post->grade_ids ?? []],
            'event_date_start' => $post->event_date_start?->format('Y-m-d'),
            'event_date_end' => $post->event_date_end?->format('Y-m-d'),
            'cover_media_id' => $post->cover_media_id,
            'newsletter_series_id' => $post->newsletter_series_id,
            'newsletter_academic_year' => $post->academic_year,
            'newsletter_term' => $post->newsletter_term,
            'newsletter_pdf_media_id' => $post->newsletter_pdf_media_id,
            'newsletter_link' => $post->newsletter_link,
        ];
        $copy = $this->posts->saveDraft(null, $post->section, $data, $request->actor(), true);

        return redirect()->route('admin.posts.edit', $copy)->with('status', 'Copy created as a draft. Change what you need, then publish.');
    }

    public function unschedule(NameOnlyRequest $request, Post $post)
    {
        $this->posts->unschedule($post, $request->actor());

        return back()->with('status', 'Unscheduled. The post is a draft again.');
    }

    public function unpin(NameOnlyRequest $request, Post $post)
    {
        $this->posts->setPin($post, null, $request->actor());

        return back()->with('status', 'Unpinned.');
    }
}
