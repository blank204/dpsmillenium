<?php

namespace Tests\Feature\Content;

use App\Content\PostService;
use App\Content\PostStatus;
use App\Content\PublishOptions;
use App\Content\Section;
use App\Models\AuditEntry;
use App\Models\MediaAsset;
use App\Models\NewsletterSeries;
use App\Models\Post;
use App\Support\Actor;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PostServiceTest extends TestCase
{
    use RefreshDatabase;

    private PostService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-09-15 10:00:00'));
        $this->service = app(PostService::class);
    }

    private function actor(string $name = 'Ms. Priya'): Actor
    {
        return new Actor($name, 1, 'editor', '10.0.0.1', 'UA', 'sess');
    }

    private function data(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Grade V Half Yearly Syllabus',
            'summary' => null,
            'blocks' => [['type' => 'text', 'html' => '<p>Please find the syllabus.</p>']],
            'grades' => ['mode' => 'none'],
        ], $overrides);
    }

    private function media(string $kind): MediaAsset
    {
        return MediaAsset::create(['kind' => $kind, 'token' => Str::random(32), 'original_name' => "f.{$kind}", 'mime' => 'x', 'bytes' => 1]);
    }

    public function test_save_draft_creates_version_and_audit(): void
    {
        $post = $this->service->saveDraft(null, Section::Circular, $this->data(), $this->actor(), false);

        $this->assertSame(PostStatus::Draft, $post->status);
        $this->assertNull($post->slug);
        $this->assertSame(1, $post->versions()->count());
        $entry = AuditEntry::where('action', 'post.draft_saved')->firstOrFail();
        $this->assertSame('Ms. Priya', $entry->actor_name);
        $this->assertSame($post->id, (int) $entry->target_id);
    }

    public function test_publish_now_sets_dates_slug_year_and_search_text(): void
    {
        $post = $this->service->saveDraft(null, Section::Circular, $this->data(), $this->actor(), false);
        $post = $this->service->publish($post, $this->data(), new PublishOptions(), $this->actor('Mr. Khan'), false);

        $this->assertSame(PostStatus::Published, $post->status);
        $this->assertSame('2026-09-15 10:00:00', $post->first_published_at->format('Y-m-d H:i:s'));
        $this->assertEquals($post->first_published_at, $post->sort_at);
        $this->assertNull($post->updated_at_public);
        $this->assertSame('grade-v-half-yearly-syllabus', $post->slug);
        $this->assertSame('2026-27', $post->academic_year);
        $this->assertStringContainsString('Please find the syllabus', $post->search_text);
        $this->assertSame('live', $post->adminStatus());
        $this->assertTrue(AuditEntry::where('action', 'post.published')->where('actor_name', 'Mr. Khan')->exists());
    }

    public function test_schedule_then_unschedule(): void
    {
        $post = $this->service->saveDraft(null, Section::Info, $this->data(), $this->actor(), false);
        $at = CarbonImmutable::parse('2026-09-20 07:00:00');

        $post = $this->service->publish($post, $this->data(), new PublishOptions(publishAt: $at), $this->actor(), false);
        $this->assertSame('scheduled', $post->adminStatus());
        $this->assertEquals($at, $post->sort_at);

        $post = $this->service->unschedule($post, $this->actor());
        $this->assertSame('draft', $post->adminStatus());
        $this->assertNull($post->publish_at);
        $this->assertTrue(AuditEntry::where('action', 'post.unscheduled')->exists());
    }

    public function test_schedule_in_past_is_rejected(): void
    {
        $post = $this->service->saveDraft(null, Section::Info, $this->data(), $this->actor(), false);
        $this->expectException(ValidationException::class);
        $this->service->publish($post, $this->data(), new PublishOptions(publishAt: now()->subMinute()->toImmutable()), $this->actor(), false);
    }

    public function test_editing_live_post_bumps_to_top_and_marks_updated(): void
    {
        $post = Post::factory()->section(Section::Circular)->published(now()->subDays(5))->create(['slug' => 'orig']);
        $this->travel(1)->hours();

        $post = $this->service->publish($post, $this->data(['title' => 'Revised syllabus']), new PublishOptions(), $this->actor(), false);

        $this->assertSame('2026-09-15 11:00:00', $post->sort_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-15 11:00:00', $post->updated_at_public->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-10', $post->first_published_at->format('Y-m-d'));
        $this->assertSame('orig', $post->slug, 'slug never changes after first publish');
        $this->assertTrue(AuditEntry::where('action', 'post.edited')->exists());
    }

    public function test_small_fix_keeps_position_but_marks_updated(): void
    {
        $post = Post::factory()->published(now()->subDays(5))->create();
        $sortBefore = $post->sort_at;

        $post = $this->service->publish($post, $this->data(), new PublishOptions(smallFix: true), $this->actor(), false);

        $this->assertEquals($sortBefore, $post->sort_at);
        $this->assertNotNull($post->updated_at_public);
    }

    public function test_live_post_cannot_be_rescheduled(): void
    {
        $post = Post::factory()->published()->create();
        $this->expectException(ValidationException::class);
        $this->service->publish($post, $this->data(), new PublishOptions(publishAt: now()->addDay()->toImmutable()), $this->actor(), false);
    }

    public function test_pin_requires_future_date_and_remove_on_must_be_future(): void
    {
        $post = Post::factory()->published()->create();

        try {
            $this->service->publish($post, $this->data(), new PublishOptions(pinnedUntil: now()->subDay()->toImmutable()), $this->actor(), false);
            $this->fail('pin in past accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('pinned_until', $e->errors());
        }

        try {
            $this->service->publish($post, $this->data(), new PublishOptions(removeOn: now()->subDay()->toImmutable()), $this->actor(), false);
            $this->fail('remove_on in past accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('remove_on', $e->errors());
        }

        $post = $this->service->publish($post, $this->data(), new PublishOptions(pinnedUntil: now()->addWeek()->toImmutable()), $this->actor(), false);
        $this->assertTrue($post->isPinnedAt(now()));
    }

    public function test_set_pin_and_unpin(): void
    {
        $post = Post::factory()->published()->create();
        $post = $this->service->setPin($post, now()->addDays(3)->toImmutable(), $this->actor());
        $this->assertTrue(AuditEntry::where('action', 'post.pinned')->exists());
        $post = $this->service->setPin($post, null, $this->actor());
        $this->assertNull($post->pinned_until);
        $this->assertTrue(AuditEntry::where('action', 'post.unpinned')->exists());
    }

    public function test_event_requires_cover_and_date_to_publish(): void
    {
        $post = $this->service->saveDraft(null, Section::Event, $this->data(), $this->actor(), false);
        try {
            $this->service->publish($post, $this->data(), new PublishOptions(), $this->actor(), false);
            $this->fail('event without cover published');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cover_media_id', $e->errors());
            $this->assertArrayHasKey('event_date_start', $e->errors());
        }

        $cover = $this->media('image');
        $post = $this->service->publish($post, $this->data(['cover_media_id' => $cover->id, 'event_date_start' => '2026-09-10']), new PublishOptions(), $this->actor(), false);
        $this->assertSame('live', $post->adminStatus());
    }

    public function test_newsletter_requires_series_year_term_cover_and_exactly_one_of_pdf_or_link(): void
    {
        $series = NewsletterSeries::create(['name' => 'Kindergarten']);
        $cover = $this->media('image');
        $pdf = $this->media('pdf');
        $base = $this->data([
            'blocks' => [],
            'newsletter_series_id' => $series->id,
            'newsletter_academic_year' => '2026-27',
            'newsletter_term' => 'Term I',
            'cover_media_id' => $cover->id,
        ]);
        $post = $this->service->saveDraft(null, Section::Newsletter, $base, $this->actor(), false);

        try {
            $this->service->publish($post, $base + ['newsletter_pdf_media_id' => $pdf->id, 'newsletter_link' => 'https://x.test/n'], new PublishOptions(), $this->actor(), false);
            $this->fail('both pdf and link accepted');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('newsletter_pdf_media_id', $e->errors());
        }

        $post = $this->service->publish($post, $base + ['newsletter_pdf_media_id' => $pdf->id], new PublishOptions(), $this->actor(), false);
        $this->assertSame('2026-27', $post->academic_year);
    }

    public function test_editor_block_rules_apply_on_publish(): void
    {
        $post = $this->service->saveDraft(null, Section::Info, $this->data(), $this->actor(), false);
        $this->expectException(ValidationException::class);
        $this->service->publish($post, $this->data(['blocks' => [['type' => 'link', 'label' => 'x', 'url' => 'javascript:1']]]), new PublishOptions(), $this->actor(), false);
    }

    public function test_delete_restore_and_purge_after_seven_days(): void
    {
        $post = Post::factory()->published()->create(['title' => 'To delete']);
        $this->service->delete($post, $this->actor());
        $this->assertSame('deleted', Post::withTrashed()->find($post->id)->adminStatus());

        $restored = $this->service->restore(Post::withTrashed()->find($post->id), $this->actor());
        $this->assertSame('live', $restored->adminStatus());

        $this->service->delete($restored, $this->actor());
        $this->travel(6)->days();
        $this->assertSame(0, $this->service->purgeExpired());
        $this->travel(1)->days();
        $this->travel(1)->minutes();
        $this->assertSame(1, $this->service->purgeExpired());

        $tomb = Post::withTrashed()->find($post->id);
        $this->assertNotNull($tomb->purged_at);
        $this->assertSame([], $tomb->blocks);
        $this->assertSame(0, $tomb->versions()->count());
        $this->assertTrue(AuditEntry::where('action', 'post.purged')->where('actor_name', 'System (scheduled)')->exists());
        $this->assertSame(['post.deleted', 'post.restored', 'post.deleted', 'post.purged'], AuditEntry::orderBy('id')->pluck('action')->all());
    }

    public function test_every_save_writes_incrementing_versions_and_restore_version(): void
    {
        $post = $this->service->saveDraft(null, Section::Info, $this->data(['title' => 'First']), $this->actor(), false);
        $post = $this->service->publish($post, $this->data(['title' => 'Second']), new PublishOptions(), $this->actor(), false);
        $post = $this->service->publish($post, $this->data(['title' => 'Third']), new PublishOptions(smallFix: true), $this->actor(), false);

        $this->assertSame([3, 2, 1], $post->versions()->pluck('version')->all());

        $v1 = $post->versions()->where('version', 1)->first();
        $post = $this->service->restoreVersion($post, $v1, $this->actor('IT'));

        $this->assertSame('First', $post->title);
        $this->assertSame('live', $post->adminStatus(), 'restore keeps the post live');
        $this->assertSame(4, $post->versions()->max('version'));
        $this->assertTrue(AuditEntry::where('action', 'post.version_restored')->where('actor_name', 'IT')->exists());
    }

    public function test_rejected_publish_leaves_model_unchanged(): void
    {
        $post = $this->service->saveDraft(null, Section::Event, $this->data(), $this->actor(), false);
        try {
            $this->service->publish($post, $this->data(['title' => 'Changed']), new PublishOptions(), $this->actor(), false);
        } catch (ValidationException) {
        }
        $this->assertSame('draft', $post->adminStatus());
        $this->assertSame('Grade V Half Yearly Syllabus', $post->title);
        $this->assertFalse(AuditEntry::where('action', 'post.published')->exists());
    }

    public function test_audit_entries_include_before_and_after(): void
    {
        $post = Post::factory()->published()->create(['title' => 'Before title']);
        $this->service->publish($post, $this->data(['title' => 'After title']), new PublishOptions(), $this->actor(), false);

        $entry = AuditEntry::where('action', 'post.edited')->firstOrFail();
        $this->assertSame('Before title', $entry->beforeData()['title']);
        $this->assertSame('After title', $entry->afterData()['title']);
    }

    public function test_restoring_invalid_version_onto_live_post_is_rejected_and_leaves_it_unchanged(): void
    {
        $post = $this->service->saveDraft(null, Section::Event, $this->data(), $this->actor(), false);
        $cover = $this->media('image');
        $post = $this->service->publish(
            $post,
            $this->data(['cover_media_id' => $cover->id, 'event_date_start' => '2026-09-10']),
            new PublishOptions(),
            $this->actor(),
            false
        );

        $v1 = $post->versions()->where('version', 1)->first();
        $titleBefore = $post->title;
        $coverBefore = $post->cover_media_id;
        $sortAtBefore = $post->sort_at;

        try {
            $this->service->restoreVersion($post, $v1, $this->actor('IT'));
            $this->fail('cover-less version restored onto a live event');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('cover_media_id', $e->errors());
        }

        $this->assertSame($titleBefore, $post->title);
        $this->assertSame($coverBefore, $post->cover_media_id);

        $fresh = Post::find($post->id);
        $this->assertSame('live', $fresh->adminStatus());
        $this->assertSame($coverBefore, $fresh->cover_media_id);
        $this->assertEquals($sortAtBefore, $fresh->sort_at);
        $this->assertFalse(AuditEntry::where('action', 'post.version_restored')->exists());
    }

    public function test_restore_version_and_delete_guards_against_trashed_posts(): void
    {
        $post = $this->service->saveDraft(null, Section::Info, $this->data(), $this->actor(), false);
        $post = $this->service->publish($post, $this->data(), new PublishOptions(), $this->actor(), false);
        $version = $post->versions()->first();

        $this->service->delete($post, $this->actor());
        $trashed = Post::withTrashed()->find($post->id);

        try {
            $this->service->restoreVersion($trashed, $version, $this->actor());
            $this->fail('restored a version onto a trashed post');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('post', $e->errors());
        }

        $deletedAtBefore = $trashed->deleted_at;
        $auditCountBefore = AuditEntry::where('action', 'post.deleted')->count();

        try {
            $this->service->delete($trashed, $this->actor());
            $this->fail('deleted an already-trashed post');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('post', $e->errors());
        }

        $this->assertEquals($deletedAtBefore, Post::withTrashed()->find($post->id)->deleted_at);
        $this->assertSame($auditCountBefore, AuditEntry::where('action', 'post.deleted')->count());
    }
}
