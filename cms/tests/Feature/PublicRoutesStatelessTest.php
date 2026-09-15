<?php

namespace Tests\Feature;

use App\Media\FileUploader;
use App\Models\Account;
use App\Models\HtmlPage;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Support\Fixtures;
use Tests\TestCase;

/**
 * Public media and custom-page responses carry long-lived public cache headers,
 * so they must never start a session or set a cookie (a shared cache could
 * otherwise store and replay an editor's session cookie).
 */
class PublicRoutesStatelessTest extends TestCase
{
    use RefreshDatabase;

    private function assertNoCookies(TestResponse $response): void
    {
        $response->assertOk();
        $this->assertSame([], $response->headers->getCookies(), 'public response must not set cookies');
        $this->assertFalse($response->headers->has('Set-Cookie'));
    }

    public function test_media_response_sets_no_cookie_for_guest_or_logged_in_editor(): void
    {
        Storage::fake('media');
        $asset = app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::jpegWithExif(500, 300), 'a.jpg'), 'image');

        $this->assertNoCookies($this->get($asset->url('400.jpg')));

        $editor = Account::factory()->editor()->create();
        $this->actingAs($editor)->withSession(['session_version' => $editor->session_version]);
        $this->assertNoCookies($this->get($asset->url('800.webp')));
    }

    public function test_sandboxed_page_response_sets_no_cookie(): void
    {
        Storage::fake('pages');
        $page = HtmlPage::create(['title' => 'Quiz', 'token' => Str::random(32), 'bytes' => 12]);
        Storage::disk('pages')->put($page->storagePath(), '<p>Hello</p>');

        $this->assertNoCookies($this->get(route('pages.sandboxed', $page->token)));
    }

    public function test_admin_routes_still_use_sessions(): void
    {
        $response = $this->get('/staff-cms/login')->assertOk();
        $this->assertNotSame([], $response->headers->getCookies());
    }

    public function test_visitor_due_tasks_still_run_from_public_media_requests(): void
    {
        Cache::flush();
        Storage::fake('media');
        $asset = app(FileUploader::class)->upload(Fixtures::uploaded(Fixtures::pdf(), 'x.pdf'), 'pdf');
        Post::factory()->scheduled(now()->subMinute())->create();

        $this->get($asset->url('file.pdf'))->assertOk();

        $this->assertSame(1, Post::where('status', 'published')->count());
    }
}
