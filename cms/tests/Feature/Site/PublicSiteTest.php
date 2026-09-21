<?php

namespace Tests\Feature\Site;

use App\Content\PostStatus;
use App\Content\Section;
use App\Models\CalendarEntry;
use App\Models\CalendarType;
use App\Models\Post;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The parent-facing pages the CMS feeds. The rule that matters most here is what a
 * visitor must NOT see: drafts, posts still waiting on their publish time, posts past
 * their removal date, and anything in the recycle bin.
 */
class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_lists_each_section_and_respects_its_count(): void
    {
        Setting::put('homepage_count.info', 2);
        foreach (['Oldest info', 'Middle info', 'Newest info'] as $i => $title) {
            Post::factory()->section(Section::Info)->published(now()->subDays(10 - $i))->create(['title' => $title]);
        }
        Post::factory()->section(Section::Circular)->published()->create(['title' => 'A circular']);

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('Newest info')->assertSee('Middle info');
        $response->assertDontSee('Oldest info');
        $response->assertSee('A circular');
        foreach (Section::cases() as $section) {
            $response->assertSee($section->label());
        }
    }

    public function test_homepage_shows_pinned_posts_first(): void
    {
        Post::factory()->section(Section::Info)->published(now())->create(['title' => 'Recent but not pinned']);
        Post::factory()->section(Section::Info)->published(now()->subMonth())
            ->create(['title' => 'Old but pinned', 'pinned_until' => now()->addWeek()]);

        $html = $this->get(route('home'))->assertOk()->getContent();

        $this->assertLessThan(
            strpos($html, 'Recent but not pinned'),
            strpos($html, 'Old but pinned'),
            'a pinned post must sort above a newer unpinned one',
        );
    }

    public function test_section_page_lists_only_that_section(): void
    {
        Post::factory()->section(Section::Circular)->published()->create(['title' => 'Exam syllabus']);
        Post::factory()->section(Section::Info)->published()->create(['title' => 'Bus timings']);

        $this->get(route('section', 'circulars'))->assertOk()
            ->assertSee('Exam syllabus')
            ->assertDontSee('Bus timings');
    }

    public function test_unknown_section_segment_is_404(): void
    {
        $this->get('/not-a-section')->assertNotFound();
    }

    public function test_post_has_its_own_shareable_page(): void
    {
        $post = Post::factory()->section(Section::Info)->published()->create([
            'title' => 'Sports day arrangements',
            'slug' => 'sports-day-arrangements',
            'summary' => 'Where to park and when to arrive.',
            'blocks' => [['type' => 'text', 'version' => 1, 'html' => '<p>Gates open at seven.</p>']],
        ]);

        $this->get($post->publicPath())->assertOk()
            ->assertSee('Sports day arrangements')
            ->assertSee('Where to park and when to arrive.')
            ->assertSee('Gates open at seven.', false);
    }

    public function test_a_stale_slug_redirects_to_the_current_address(): void
    {
        $post = Post::factory()->section(Section::Info)->published()->create(['title' => 'New name', 'slug' => 'new-name']);

        $this->get('/information/'.$post->id.'-the-old-name')
            ->assertRedirect($post->publicPath());
    }

    public function test_a_post_is_not_reachable_from_the_wrong_section(): void
    {
        $post = Post::factory()->section(Section::Info)->published()->create(['slug' => 'a-notice']);

        $this->get('/circulars/'.$post->id.'-a-notice')->assertNotFound();
    }

    public static function hiddenPosts(): array
    {
        return [
            'draft' => [fn () => Post::factory()->draft()->create(['title' => 'Secret draft', 'slug' => 'secret-draft'])],
            'scheduled for later' => [fn () => Post::factory()->scheduled(CarbonImmutable::now()->addWeek())->create(['title' => 'Not yet', 'slug' => 'not-yet'])],
            'past its removal date' => [fn () => Post::factory()->published(CarbonImmutable::now()->subMonth())->create(['title' => 'Expired', 'slug' => 'expired', 'remove_on' => CarbonImmutable::now()->subDay()])],
            'in the recycle bin' => [fn () => tap(Post::factory()->published()->create(['title' => 'Deleted', 'slug' => 'deleted']), fn ($p) => $p->delete())],
            'purged' => [fn () => Post::factory()->published()->create(['title' => 'Purged', 'slug' => 'purged', 'purged_at' => CarbonImmutable::now()])],
        ];
    }

    #[DataProvider('hiddenPosts')]
    public function test_visitors_never_see_a_post_that_is_not_live(callable $make): void
    {
        $post = $make();

        $this->get(route('home'))->assertOk()->assertDontSee($post->title);
        $this->get(route('section', $post->section->pathSegment()))->assertOk()->assertDontSee($post->title);
        $this->get(route('archive'))->assertOk()->assertDontSee($post->title);
        $this->get('/'.$post->section->pathSegment().'/'.$post->id.'-'.$post->slug)->assertNotFound();
    }

    public function test_a_scheduled_post_becomes_visible_once_its_time_passes(): void
    {
        $post = Post::factory()->section(Section::Info)->scheduled(CarbonImmutable::now()->addHour())
            ->create(['title' => 'Goes live soon', 'slug' => 'goes-live-soon']);

        $this->get($post->publicPath())->assertNotFound();

        $this->travel(2)->hours();

        $this->get($post->publicPath())->assertOk()->assertSee('Goes live soon');
    }

    public function test_public_pages_set_no_cookies(): void
    {
        Post::factory()->section(Section::Info)->published()->create(['slug' => 'a-notice']);

        foreach ([route('home'), route('section', 'information'), route('archive'), route('calendar')] as $url) {
            $response = $this->get($url)->assertOk();
            $this->assertSame([], $response->headers->getCookies(), "{$url} must not set a cookie");
            $this->assertFalse($response->headers->has('Set-Cookie'), "{$url} must not set a cookie");
        }
    }

    public function test_calendar_shows_entries_and_navigates_by_month(): void
    {
        $type = CalendarType::firstOrCreate(['name' => 'Holiday']);
        CalendarEntry::create([
            'title' => 'National Day',
            'calendar_type_id' => $type->id,
            'start_date' => '2026-12-18',
            'end_date' => '2026-12-19',
            'grade_mode' => 'all',
        ]);

        // The month grid only carries that month's entries; the "Coming up" list below
        // it is deliberately not limited to the month being shown.
        $grid = fn (int $month) => strstr(
            $this->get(route('calendar', ['year' => 2026, 'month' => $month]))->assertOk()->getContent(),
            'Coming up',
            true,
        );

        $this->assertStringContainsString('National Day', $grid(12));
        $this->assertStringNotContainsString('National Day', $grid(10));

        $this->get(route('calendar', ['year' => 2026, 'month' => 13]))->assertNotFound();
    }

    public function test_draft_post_has_no_public_path(): void
    {
        $this->assertNull(Post::factory()->draft()->create()->publicPath());
    }

    public function test_pages_carry_a_canonical_url_and_description(): void
    {
        $post = Post::factory()->section(Section::Circular)->published()
            ->create(['title' => 'Grade V syllabus', 'slug' => 'grade-v-syllabus', 'summary' => 'All subjects.']);

        $this->get($post->publicPath())->assertOk()
            ->assertSee('<link rel="canonical" href="'.url($post->publicPath()).'">', false)
            ->assertSee('<meta name="description" content="All subjects.">', false);
    }

    public function test_status_is_derived_not_trusted_from_the_status_column_alone(): void
    {
        // A row left as Published but past remove_on must still be hidden.
        $post = Post::factory()->published(CarbonImmutable::now()->subWeek())->create([
            'status' => PostStatus::Published,
            'slug' => 'gone',
            'remove_on' => CarbonImmutable::now()->subHour(),
        ]);

        $this->get($post->publicPath())->assertNotFound();
    }
}
