<?php

namespace App\Scheduling;

use App\Audit\AuditLogger;
use App\Content\PostService;
use App\Content\PostStatus;
use App\Models\Post;
use App\Support\Actor;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class DueTasks
{
    public function __construct(
        private readonly PostService $posts,
        private readonly AuditLogger $audit,
    ) {}

    /** @return array{published: int, unpinned: int, purged: int} */
    public function run(?CarbonInterface $now = null): array
    {
        $now = CarbonImmutable::instance($now ?? CarbonImmutable::now());
        $published = 0;

        Post::where('status', PostStatus::Scheduled->value)->where('publish_at', '<=', $now)->each(function (Post $post) use (&$published) {
            DB::transaction(function () use ($post) {
                $before = $post->snapshot();
                $post->status = PostStatus::Published;
                $post->save();
                $this->audit->record('post.auto_published', Actor::system(), $post, $before, $post->snapshot());
            });
            $published++;
        });

        $unpinned = Post::whereNotNull('pinned_until')->where('pinned_until', '<=', $now)->update(['pinned_until' => null]);

        return [
            'published' => $published,
            'unpinned' => $unpinned,
            'purged' => $this->posts->purgeExpired($now),
        ];
    }
}
