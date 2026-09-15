<?php

namespace Tests\Feature\Scheduling;

use App\Content\PostStatus;
use App\Models\Post;
use App\Scheduling\DueTasks;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\IndependentConnections;
use Tests\TestCase;

/**
 * Ledger T10 / review Minor 1: cron and the visitor fallback use separate
 * throttles, so both can pick the same due post. The second runner must wait
 * for the first and then skip it, never publishing or auditing it twice.
 */
class DueTasksConcurrencyTest extends TestCase
{
    use IndependentConnections;
    use RefreshDatabase;

    public function test_cron_and_visitor_runner_publish_and_audit_a_post_once(): void
    {
        $cron = $this->independentConnection('runner_cron');
        $visitor = $this->independentConnection('runner_visitor');
        $baselineAuditId = (int) $cron->table('audit_entries')->max('id');
        $headBefore = (array) $cron->table('audit_chain_head')->where('id', 1)->first();
        $tasks = app(DueTasks::class);
        $now = CarbonImmutable::now();
        $postId = $this->onConnection('runner_cron', fn () => Post::factory()->scheduled(now()->subMinute())->create()->id);

        try {
            $cron->beginTransaction();
            $this->assertTrue($this->onConnection('runner_cron', fn () => $tasks->publishDue($postId, $now)));

            try {
                $this->onConnection('runner_visitor', fn () => $tasks->publishDue($postId, $now));
                $this->fail('the visitor runner did not wait for the cron runner\'s row lock');
            } catch (QueryException $e) {
                $this->assertStringContainsStringIgnoringCase('lock wait timeout', $e->getMessage());
            }

            $cron->commit();

            $this->assertFalse($this->onConnection('runner_visitor', fn () => $tasks->publishDue($postId, $now)));
            $this->assertSame(PostStatus::Published->value, $visitor->table('posts')->where('id', $postId)->value('status'));
            $this->assertSame(1, $visitor->table('audit_entries')->where('action', 'post.auto_published')->where('target_id', $postId)->count());
        } finally {
            $this->closeIndependentConnections();
            $cleanup = $this->independentConnection('runner_cleanup');
            $cleanup->table('audit_entries')->where('id', '>', $baselineAuditId)->delete();
            $cleanup->table('audit_chain_head')->where('id', 1)->update($headBefore);
            $cleanup->table('post_versions')->where('post_id', $postId)->delete();
            $cleanup->table('posts')->where('id', $postId)->delete();
            $this->closeIndependentConnections();
        }
    }
}
