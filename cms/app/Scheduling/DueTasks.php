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

        // lazyById (keyset paging) rather than each() (offset paging): publishing
        // removes rows from this filter, which made offset paging skip posts.
        Post::where('status', PostStatus::Scheduled->value)->where('publish_at', '<=', $now)
            ->select('id')->lazyById(200)
            ->each(function (Post $candidate) use ($now, &$published) {
                if ($this->publishDue($candidate->id, $now)) {
                    $published++;
                }
            });

        $unpinned = Post::whereNotNull('pinned_until')->where('pinned_until', '<=', $now)->update(['pinned_until' => null]);

        return [
            'published' => $published,
            'unpinned' => $unpinned,
            'purged' => $this->posts->purgeExpired($now),
        ];
    }

    /**
     * Publish one due post if, and only if, it is still scheduled and due once its
     * row is locked. Cron and the visitor fallback can run at the same moment; the
     * second runner waits on the row lock, re-reads the post as published (or
     * unscheduled/rescheduled meanwhile) and does nothing, so a post is never
     * published or audited twice. Works the same on MySQL 8 and MariaDB 10.6+.
     */
    public function publishDue(int $postId, CarbonInterface $now): bool
    {
        return DB::transaction(function () use ($postId, $now) {
            $post = Post::whereKey($postId)->lockForUpdate()->first();
            if ($post === null || $post->status !== PostStatus::Scheduled || $post->publish_at === null || $post->publish_at->gt($now)) {
                return false;
            }

            $before = $post->snapshot();
            $post->status = PostStatus::Published;
            $post->save();
            $this->audit->record('post.auto_published', Actor::system(), $post, $before, $post->snapshot());

            return true;
        });
    }
}
