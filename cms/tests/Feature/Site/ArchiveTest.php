<?php

namespace Tests\Feature\Site;

use App\Content\Section;
use App\Models\Post;
use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Tests\TestCase;

/**
 * One archive across all four sections, filtered by section, school year and month,
 * with a search over titles and body text. The school year boundary comes from the
 * configurable academic_year_start_month, not from January.
 */
class ArchiveTest extends TestCase
{
    /*
     * Not RefreshDatabase: InnoDB only adds rows to a FULLTEXT index when their
     * transaction commits, so a MATCH ... AGAINST search inside the usual test
     * transaction would find nothing and this file would be testing the LIKE
     * fallback instead of the query the site actually runs.
     */
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put('academic_year_start_month', 4);
    }

    private function publish(string $title, Section $section, string $date, string $body = ''): Post
    {
        $at = CarbonImmutable::parse($date);

        return Post::factory()->section($section)->published($at)->create([
            'title' => $title,
            'slug' => \Illuminate\Support\Str::slug($title),
            'search_text' => $body,
        ]);
    }

    public function test_archive_lists_every_section_by_default(): void
    {
        $this->publish('Bus timings', Section::Info, '2026-05-02');
        $this->publish('Exam syllabus', Section::Circular, '2026-06-02');
        $this->publish('Sports day report', Section::Event, '2026-07-02');

        $this->get(route('archive'))->assertOk()
            ->assertSee('Bus timings')->assertSee('Exam syllabus')->assertSee('Sports day report');
    }

    public function test_archive_filters_by_section(): void
    {
        $this->publish('Bus timings', Section::Info, '2026-05-02');
        $this->publish('Exam syllabus', Section::Circular, '2026-06-02');

        $this->get(route('archive', ['section' => 'circular']))->assertOk()
            ->assertSee('Exam syllabus')->assertDontSee('Bus timings');
    }

    public function test_archive_filters_by_school_year_not_calendar_year(): void
    {
        // With an April start, March 2026 belongs to 2025-26 and April 2026 to 2026-27.
        $this->publish('March notice', Section::Info, '2026-03-20');
        $this->publish('April notice', Section::Info, '2026-04-20');

        $this->get(route('archive', ['year' => '2026-27']))->assertOk()
            ->assertSee('April notice')->assertDontSee('March notice');

        $this->get(route('archive', ['year' => '2025-26']))->assertOk()
            ->assertSee('March notice')->assertDontSee('April notice');
    }

    public function test_archive_filters_by_month(): void
    {
        $this->publish('May notice', Section::Info, '2026-05-10');
        $this->publish('June notice', Section::Info, '2026-06-10');

        $this->get(route('archive', ['month' => 6]))->assertOk()
            ->assertSee('June notice')->assertDontSee('May notice');
    }

    public function test_archive_searches_titles_and_body_text(): void
    {
        $this->publish('Half yearly examination syllabus', Section::Circular, '2026-05-02');
        $this->publish('Annual day', Section::Event, '2026-05-03', 'Parents are invited to the auditorium.');
        $this->publish('Bus timings', Section::Info, '2026-05-04');

        $this->get(route('archive', ['q' => 'syllabus']))->assertOk()
            ->assertSee('Half yearly examination syllabus')->assertDontSee('Bus timings');

        $this->get(route('archive', ['q' => 'auditorium']))->assertOk()
            ->assertSee('Annual day')->assertDontSee('Bus timings');
    }

    public function test_short_search_words_still_work(): void
    {
        // Words under MySQL's full-text minimum token length fall back to LIKE.
        $this->publish('PTM on Thursday', Section::Info, '2026-05-02');
        $this->publish('Bus timings', Section::Info, '2026-05-03');

        $this->get(route('archive', ['q' => 'PTM']))->assertOk()
            ->assertSee('PTM on Thursday')->assertDontSee('Bus timings');
    }

    public function test_search_with_no_results_explains_itself(): void
    {
        $this->publish('Bus timings', Section::Info, '2026-05-02');

        $this->get(route('archive', ['q' => 'nothingmatchesthisword']))->assertOk()
            ->assertSee('Nothing matches what you asked for.');
    }

    public function test_a_post_still_on_the_homepage_is_also_in_the_archive(): void
    {
        $post = $this->publish('Todays notice', Section::Info, CarbonImmutable::now()->toDateString());

        $this->get(route('home'))->assertOk()->assertSee('Todays notice');
        $this->get(route('archive'))->assertOk()->assertSee('Todays notice');
        $this->assertNotNull($post->publicPath());
    }

    public function test_a_post_past_its_removal_date_is_not_in_the_archive(): void
    {
        $this->publish('Old notice', Section::Info, '2026-05-02')
            ->update(['remove_on' => CarbonImmutable::now()->subDay()]);

        $this->get(route('archive'))->assertOk()->assertDontSee('Old notice');
    }

    public function test_the_year_filter_only_offers_years_that_have_posts(): void
    {
        $this->publish('Only notice', Section::Info, '2026-05-02');

        $this->get(route('archive'))->assertOk()
            ->assertSee('2026-27')
            ->assertDontSee('2019-20');
    }

    public function test_a_nonsense_year_is_ignored_rather_than_crashing(): void
    {
        $this->publish('Bus timings', Section::Info, '2026-05-02');

        $this->get(route('archive', ['year' => 'not-a-year', 'month' => 99]))->assertOk()
            ->assertSee('Bus timings');
    }
}
