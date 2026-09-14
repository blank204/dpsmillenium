<?php

namespace Tests\Feature\Content;

use App\Content\HomepageBox;
use App\Content\Section;
use App\Models\Post;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow('2026-09-15 10:00:00');
        $this->travelTo(CarbonImmutable::now());
    }

    public function test_drafts_are_not_public(): void
    {
        Post::factory()->draft()->create();
        $this->assertSame(0, Post::publicEligible()->count());
    }

    public function test_published_is_public(): void
    {
        Post::factory()->published()->create();
        $this->assertSame(1, Post::publicEligible()->count());
    }

    public function test_future_scheduled_is_not_public_but_past_due_is(): void
    {
        Post::factory()->scheduled(now()->addHour())->create();
        $due = Post::factory()->scheduled(now()->subMinute())->create();

        $this->assertSame([$due->id], Post::publicEligible()->pluck('id')->all());
    }

    public function test_remove_on_hides_post_at_exact_time(): void
    {
        Post::factory()->published()->create(['remove_on' => now()]);
        Post::factory()->published()->create(['remove_on' => now()->addSecond()]);
        $this->assertSame(1, Post::publicEligible()->count());
    }

    public function test_soft_deleted_is_not_public(): void
    {
        Post::factory()->published()->create()->delete();
        $this->assertSame(0, Post::publicEligible()->count());
    }

    public function test_homepage_box_takes_top_n_with_pins_first(): void
    {
        Setting::put('homepage_count.circular', 3);
        $old = Post::factory()->section(Section::Circular)->published(now()->subDays(10))->create(['pinned_until' => now()->addDay()]);
        $a = Post::factory()->section(Section::Circular)->published(now()->subDays(1))->create();
        $b = Post::factory()->section(Section::Circular)->published(now()->subDays(2))->create();
        Post::factory()->section(Section::Circular)->published(now()->subDays(3))->create();
        Post::factory()->section(Section::Info)->published(now())->create();

        $this->assertSame([$old->id, $a->id, $b->id], HomepageBox::items(Section::Circular)->pluck('id')->all());
    }

    public function test_expired_pin_orders_normally(): void
    {
        Setting::put('homepage_count.info', 2);
        $expired = Post::factory()->section(Section::Info)->published(now()->subDays(10))->create(['pinned_until' => now()->subMinute()]);
        $new = Post::factory()->section(Section::Info)->published(now()->subDay())->create();

        $this->assertSame([$new->id, $expired->id], HomepageBox::items(Section::Info)->pluck('id')->all());
    }

    public function test_archive_query_includes_posts_still_in_box(): void
    {
        Setting::put('homepage_count.info', 1);
        Post::factory()->section(Section::Info)->published(now()->subDay())->create();
        Post::factory()->section(Section::Info)->published(now()->subDays(2))->create();

        $this->assertSame(2, Post::publicEligible()->ofSection(Section::Info)->count());
    }

    public function test_admin_status(): void
    {
        $this->assertSame('draft', Post::factory()->draft()->create()->adminStatus());
        $this->assertSame('scheduled', Post::factory()->scheduled(now()->addDay())->create()->adminStatus());
        $this->assertSame('live', Post::factory()->published()->create()->adminStatus());
        $this->assertSame('hidden', Post::factory()->published()->create(['remove_on' => now()->subDay()])->adminStatus());
        $deleted = Post::factory()->published()->create();
        $deleted->delete();
        $this->assertSame('deleted', $deleted->adminStatus());
    }

    public function test_public_path_uses_section_segment_id_and_slug(): void
    {
        $post = Post::factory()->section(Section::Circular)->published()->create(['slug' => 'grade-v-syllabus']);
        $this->assertSame("/circulars/{$post->id}-grade-v-syllabus", $post->publicPath());
        $this->assertNull(Post::factory()->draft()->create(['slug' => null])->publicPath());
    }
}
