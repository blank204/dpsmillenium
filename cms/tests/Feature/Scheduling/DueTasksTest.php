<?php

namespace Tests\Feature\Scheduling;

use App\Content\PostStatus;
use App\Models\AuditEntry;
use App\Models\Post;
use App\Models\Setting;
use App\Scheduling\DueTasks;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DueTasksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-09-15 07:00:00'));
    }

    public function test_publishes_due_scheduled_posts_only(): void
    {
        $due = Post::factory()->scheduled(now()->subMinute())->create();
        $future = Post::factory()->scheduled(now()->addHour())->create();

        $result = app(DueTasks::class)->run();

        $this->assertSame(1, $result['published']);
        $this->assertSame(PostStatus::Published, $due->fresh()->status);
        $this->assertSame(PostStatus::Scheduled, $future->fresh()->status);
        $this->assertTrue(AuditEntry::where('action', 'post.auto_published')->where('target_id', $due->id)->where('actor_name', 'System (scheduled)')->exists());
    }

    public function test_running_twice_publishes_and_audits_once(): void
    {
        $due = Post::factory()->scheduled(now()->subMinute())->create();

        $this->assertSame(1, app(DueTasks::class)->run()['published']);
        $this->assertSame(0, app(DueTasks::class)->run()['published']);

        $this->assertSame(1, AuditEntry::where('action', 'post.auto_published')->where('target_id', $due->id)->count());
    }

    public function test_publish_due_rechecks_state_under_lock(): void
    {
        $tasks = app(DueTasks::class);
        $now = CarbonImmutable::now();

        $post = Post::factory()->scheduled(now()->subMinute())->create();
        $this->assertTrue($tasks->publishDue($post->id, $now));
        $this->assertFalse($tasks->publishDue($post->id, $now), 'already published by another runner');

        // Unscheduled between the runner's candidate query and its update.
        $unscheduled = Post::factory()->scheduled(now()->subMinute())->create();
        Post::whereKey($unscheduled->id)->update(['status' => PostStatus::Draft->value]);
        $this->assertFalse($tasks->publishDue($unscheduled->id, $now));
        $this->assertSame(PostStatus::Draft, $unscheduled->fresh()->status);

        // Rescheduled to the future in the same window.
        $moved = Post::factory()->scheduled(now()->subMinute())->create();
        Post::whereKey($moved->id)->update(['publish_at' => now()->addDay()]);
        $this->assertFalse($tasks->publishDue($moved->id, $now));
        $this->assertSame(PostStatus::Scheduled, $moved->fresh()->status);

        $this->assertSame(1, AuditEntry::where('action', 'post.auto_published')->count());
    }

    public function test_purging_twice_audits_once(): void
    {
        $post = Post::factory()->published()->create();
        $post->delete();
        $this->travel(8)->days();

        $posts = app(\App\Content\PostService::class);
        $this->assertSame(1, $posts->purgeExpired());
        $this->assertSame(0, $posts->purgeExpired());
        $this->assertSame(1, AuditEntry::where('action', 'post.purged')->count());
    }

    public function test_clears_expired_pins(): void
    {
        $expired = Post::factory()->published()->create(['pinned_until' => now()->subMinute()]);
        $active = Post::factory()->published()->create(['pinned_until' => now()->addDay()]);

        $this->assertSame(1, app(DueTasks::class)->run()['unpinned']);
        $this->assertNull($expired->fresh()->pinned_until);
        $this->assertNotNull($active->fresh()->pinned_until);
    }

    public function test_purges_recycle_bin(): void
    {
        $post = Post::factory()->published()->create();
        $post->delete();
        $this->travel(8)->days();

        $this->assertSame(1, app(DueTasks::class)->run()['purged']);
    }

    public function test_command_records_cron_source(): void
    {
        $this->artisan('cms:due-tasks')->assertSuccessful();
        $this->assertSame('cron', Setting::get('scheduler.last_source'));
    }

    public function test_visitor_fallback_runs_at_most_once_per_minute(): void
    {
        Cache::flush();
        Post::factory()->scheduled(now()->subMinute())->create();

        $this->get('/staff-cms/login')->assertOk();
        $this->assertSame(1, Post::where('status', 'published')->count());
        $this->assertSame('visitor', Setting::get('scheduler.last_source'));

        Post::factory()->scheduled(now()->subSecond())->create();
        $this->get('/staff-cms/login');
        $this->assertSame(1, Post::where('status', 'published')->count(), 'second request inside 60s must not run tasks');

        $this->travel(61)->seconds();
        $this->get('/staff-cms/login');
        $this->assertSame(2, Post::where('status', 'published')->count());
    }
}
