<?php

namespace Tests\Feature;

use App\Content\Section;
use App\Models\Account;
use App\Models\CalendarEntry;
use App\Models\CalendarType;
use App\Models\HtmlPage;
use App\Models\Post;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Every page renders, and every link on it goes somewhere real. Screens are wired by
 * hand from a design, so the failure this guards against is a plain broken link — a
 * route renamed, a nav entry pointing at nothing, a button with no page behind it.
 */
class LinksResolveTest extends TestCase
{
    use RefreshDatabase;

    /** Links that intentionally leave the application. */
    private const EXTERNAL_PREFIXES = ['http://', 'https://', 'mailto:', 'tel:', '#'];

    private function seedContent(): array
    {
        $type = CalendarType::firstOrCreate(['name' => 'Holiday']);
        CalendarEntry::create([
            'title' => 'National Day',
            'calendar_type_id' => $type->id,
            'start_date' => CarbonImmutable::now()->toDateString(),
            'grade_mode' => 'all',
        ]);

        $posts = [];
        foreach (Section::cases() as $section) {
            $posts[$section->value] = Post::factory()->section($section)->published()
                ->create(['title' => 'Live '.$section->value, 'slug' => 'live-'.$section->value]);
        }
        $draft = Post::factory()->draft()->create(['title' => 'A draft']);
        $binned = Post::factory()->published()->create(['title' => 'Binned', 'slug' => 'binned']);
        $binned->delete();

        $page = HtmlPage::create(['token' => Str::random(32), 'title' => 'Seating plan', 'bytes' => 100]);

        return ['post' => $posts['info'], 'draft' => $draft, 'page' => $page, 'entry' => CalendarEntry::firstOrFail()];
    }

    /** @return string[] every href/action on the page, absolute or root-relative */
    private function linksIn(string $html): array
    {
        preg_match_all('/(?:href|action)="([^"]*)"/i', $html, $matches);

        return collect($matches[1])
            ->map(fn ($l) => html_entity_decode($l, ENT_QUOTES))
            ->reject(fn ($l) => $l === '' || Str::startsWith($l, self::EXTERNAL_PREFIXES))
            ->unique()
            ->values()
            ->all();
    }

    private function assertLinksResolve(string $from, string $html, string $role): void
    {
        foreach ($this->linksIn($html) as $link) {
            $path = parse_url($link, PHP_URL_PATH) ?: '/';
            $this->assertNotNull(
                Route::getRoutes()->match(\Illuminate\Http\Request::create($path, 'GET')),
                "{$role} page {$from} links to {$link}, which matches no route",
            );
        }
    }

    public function test_every_admin_screen_renders_and_its_links_go_somewhere(): void
    {
        $fixtures = $this->seedContent();
        $account = Account::factory()->superAdmin()->create();
        $this->actingAs($account)->withSession(['session_version' => $account->session_version]);

        $urls = [
            route('admin.dashboard'),
            route('admin.posts.index'),
            route('admin.posts.choose'),
            route('admin.posts.create', 'info'),
            route('admin.posts.create', 'event'),
            route('admin.posts.create', 'newsletter'),
            route('admin.posts.edit', $fixtures['post']),
            route('admin.posts.edit', $fixtures['draft']),
            route('admin.posts.versions', $fixtures['post']),
            route('admin.calendar.index'),
            route('admin.calendar.index', ['edit' => $fixtures['entry']->id]),
            route('admin.recycle.index'),
            route('admin.audit.index'),
            route('admin.settings.edit'),
            route('admin.security.edit'),
            route('admin.pages.index'),
            route('admin.system.show'),
            route('admin.tutorial'),
        ];

        foreach ($urls as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertLinksResolve($url, $html, 'super admin');
        }
    }

    public function test_the_editor_sees_no_link_they_are_not_allowed_to_follow(): void
    {
        $fixtures = $this->seedContent();
        $editor = Account::factory()->editor()->create();
        $this->actingAs($editor)->withSession(['session_version' => $editor->session_version]);

        $urls = [
            route('admin.dashboard'),
            route('admin.posts.index'),
            route('admin.posts.choose'),
            route('admin.posts.edit', $fixtures['post']),
            route('admin.calendar.index'),
            route('admin.recycle.index'),
            route('admin.tutorial'),
        ];

        foreach ($urls as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertLinksResolve($url, $html, 'editor');

            foreach ($this->linksIn($html) as $link) {
                $path = parse_url($link, PHP_URL_PATH) ?: '/';
                $route = Route::getRoutes()->match(\Illuminate\Http\Request::create($path, 'GET'));
                $this->assertNotContains(
                    'role:super_admin',
                    $route->gatherMiddleware(),
                    "the editor page {$url} offers {$link}, which only a super admin may open",
                );
            }
        }
    }

    public function test_every_public_page_renders_and_its_links_go_somewhere(): void
    {
        $fixtures = $this->seedContent();

        $urls = [
            route('home'), route('calendar'), route('archive'), $fixtures['post']->publicPath(),
            route('about'), route('campus'), route('fees'), route('partnerships'),
        ];
        foreach (Section::cases() as $section) {
            $urls[] = route('section', $section->pathSegment());
        }

        foreach ($urls as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertLinksResolve($url, $html, 'visitor');
        }
    }

    public function test_the_shared_nav_reaches_every_public_page(): void
    {
        $this->seedContent();
        $html = $this->get(route('home'))->assertOk()->getContent();

        $expected = [route('about'), route('campus'), route('fees'), route('partnerships'), route('calendar'), route('archive')];
        foreach (Section::cases() as $section) {
            $expected[] = route('section', $section->pathSegment());
        }

        foreach ($expected as $target) {
            $this->assertStringContainsString('href="'.$target.'"', $html, "the nav does not link to {$target}");
        }
    }

    public function test_the_site_assets_the_marketing_pages_reference_exist(): void
    {
        foreach (['site/site.css', 'site/site.js', 'site/home.css', 'site/home.js'] as $asset) {
            $this->assertFileExists(public_path($asset));
        }
        foreach (['shield', 'seal', 'seal-l1-ring', 'seal-l8-lettering'] as $svg) {
            $this->assertFileExists(public_path("site/assets/{$svg}.svg"));
        }
        foreach (['campus-sketch', 'campus-concept'] as $jpg) {
            $this->assertFileExists(public_path("site/assets/{$jpg}.jpeg"));
        }
    }

    public function test_no_public_page_links_into_the_staff_cms(): void
    {
        $fixtures = $this->seedContent();
        $adminPath = '/'.config('cms.admin_path');

        foreach ([route('home'), route('calendar'), route('archive'), $fixtures['post']->publicPath()] as $url) {
            foreach ($this->linksIn($this->get($url)->assertOk()->getContent()) as $link) {
                $this->assertStringNotContainsString($adminPath, $link, "{$url} exposes a staff link");
            }
        }
    }

    public function test_the_admin_stylesheet_and_scripts_are_actually_there(): void
    {
        foreach (['admin/admin.css', 'admin/editor.js', 'admin/grade-picker.js', 'site/site.css', 'img/shield.svg'] as $asset) {
            $this->assertFileExists(public_path($asset), "{$asset} is referenced by a view but missing from public/");
        }

        foreach (['plex-sans-400', 'plex-sans-500', 'plex-sans-600', 'plex-serif-400', 'plex-serif-600'] as $font) {
            $this->assertFileExists(public_path("admin/fonts/{$font}.woff2"));
        }
    }
}
