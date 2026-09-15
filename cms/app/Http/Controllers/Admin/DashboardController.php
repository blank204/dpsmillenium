<?php

namespace App\Http\Controllers\Admin;

use App\Content\PostStatus;
use App\Http\Controllers\Controller;
use App\Models\Post;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $now = now();

        return view('admin.dashboard', [
            'recent' => Post::publicEligible($now)->orderByDesc('sort_at')->limit(10)->get(),
            'scheduled' => Post::where('status', PostStatus::Scheduled->value)->where('publish_at', '>', $now)->orderBy('publish_at')->get(),
            'pinned' => Post::publicEligible($now)->where('pinned_until', '>', $now)->orderBy('pinned_until')->get(),
            'drafts' => Post::where('status', PostStatus::Draft->value)->orderByDesc('updated_at')->limit(20)->get(),
        ]);
    }
}
