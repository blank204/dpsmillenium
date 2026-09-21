<?php

namespace Tests\Feature\Admin;

use App\Audit\AuditLogger;
use App\Models\Account;
use App\Models\AuditEntry;
use App\Models\CalendarEntry;
use App\Models\CalendarType;
use App\Models\HtmlPage;
use App\Models\MediaAsset;
use App\Models\Post;
use App\Models\PostVersion;
use App\Support\Actor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RouteDefinition;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Spec §15: "Name required on every mutating action" and "Editor cannot access
 * Super admin routes". Enforcement is by convention (ActorRequest, route group),
 * so this sweeps every admin route instead of trusting each screen's own test.
 * Any new admin route is automatically covered; exemptions must be listed here.
 */
class RouteMatrixTest extends TestCase
{
    use RefreshDatabase;

    /** Non-GET admin routes that deliberately do not require a typed name. */
    private const ACTOR_NAME_EXEMPT = [
        'admin.login.attempt' => 'Logging in: there is no session yet; success and failure are audited against the account.',
        'admin.logout' => 'Ending your own session changes no content; audited with the account.',
        'admin.media.upload' => 'Creates a new unreferenced file only; nothing public changes until a post using it is saved with a name.',
        'admin.posts.preview' => 'Renders an unsaved preview; writes nothing.',
        'admin.audit.verify' => 'Ruling T13: read-only integrity check (Super admin only); accepted without a name.',
    ];

    /** Admin routes an editor may use. Every other admin route must be Super admin only. */
    private const EDITOR_ROUTES = [
        'admin.login', 'admin.login.attempt', 'admin.logout', 'admin.dashboard',
        'admin.posts.index', 'admin.posts.choose', 'admin.posts.create', 'admin.posts.store', 'admin.posts.preview',
        'admin.posts.edit', 'admin.posts.update', 'admin.posts.destroy',
        'admin.posts.duplicate', 'admin.posts.unschedule', 'admin.posts.unpin',
        'admin.media.upload', 'admin.media.edit',
        'admin.calendar.index', 'admin.calendar.store', 'admin.calendar.update', 'admin.calendar.destroy',
        'admin.recycle.index',
        'admin.tutorial', // static help page, no data and nothing to change
        'staff-cms/_actor-probe', // testing-only probe route (unnamed)
    ];

    /** @return array<string, RouteDefinition> keyed by route name (or URI when unnamed) */
    private function adminRoutes(): array
    {
        $prefix = config('cms.admin_path');
        $routes = [];
        foreach (Route::getRoutes()->getRoutes() as $route) {
            if ($route->uri() === $prefix || str_starts_with($route->uri(), $prefix.'/')) {
                // Unnamed routes inherit just the group prefix ("admin."), so key those by URI.
                $name = (string) $route->getName();
                $routes[$name !== '' && ! str_ends_with($name, '.') ? $name : $route->uri()] = $route;
            }
        }
        ksort($routes);

        return $routes;
    }

    private function isMutating(RouteDefinition $route): bool
    {
        return array_diff($route->methods(), ['GET', 'HEAD']) !== [];
    }

    private function method(RouteDefinition $route): string
    {
        return strtolower(array_values(array_diff($route->methods(), ['HEAD']))[0]);
    }

    /** Build a URL with real, bindable models for every route parameter. */
    private function url(RouteDefinition $route, array $fixtures): string
    {
        $uri = $route->uri();
        $params = [];
        foreach ($route->parameterNames() as $name) {
            $params[$name] = match ($name) {
                'post' => $fixtures['post']->id,
                'version' => $fixtures['version']->id,
                'id' => $fixtures['trashed']->id,
                'section' => 'info',
                'asset' => $fixtures['asset']->id,
                'page' => $fixtures['page']->id,
                'entry' => str_starts_with($uri, config('cms.admin_path').'/change-log') ? $fixtures['audit']->id : $fixtures['calendar']->id,
                default => $this->fail("RouteMatrixTest has no fixture for route parameter {{$name}} on {$uri}"),
            };
        }
        foreach ($params as $name => $value) {
            $uri = str_replace(['{'.$name.'}', '{'.$name.'?}'], (string) $value, $uri);
        }

        return '/'.$uri;
    }

    private function fixtures(): array
    {
        $post = Post::factory()->published()->create();
        $trashed = Post::factory()->published()->create();
        $trashed->delete();

        return [
            'post' => $post,
            'trashed' => $trashed,
            'version' => PostVersion::create(['post_id' => $post->id, 'version' => 1, 'data' => $post->snapshot(), 'actor_name' => 'Seed', 'created_at' => now()]),
            'asset' => MediaAsset::create(['kind' => 'image', 'token' => Str::random(32), 'original_name' => 'a.jpg', 'mime' => 'image/jpeg', 'bytes' => 1]),
            'page' => HtmlPage::create(['token' => Str::random(32), 'title' => 'Quiz', 'bytes' => 1]),
            'calendar' => CalendarEntry::create(['title' => 'Exam', 'calendar_type_id' => CalendarType::query()->value('id'), 'start_date' => '2026-10-01']),
            'audit' => app(AuditLogger::class)->record('seed', Actor::system()),
        ];
    }

    private function login(Account $account): void
    {
        $this->actingAs($account)->withSession(['session_version' => $account->session_version]);
    }

    public function test_every_mutating_admin_route_rejects_a_missing_actor_name(): void
    {
        $fixtures = $this->fixtures();
        $super = Account::factory()->superAdmin()->create();
        $checked = [];

        foreach ($this->adminRoutes() as $key => $route) {
            if (! $this->isMutating($route) || array_key_exists($key, self::ACTOR_NAME_EXEMPT)) {
                continue;
            }

            $this->login($super->fresh());
            $auditRows = AuditEntry::count();

            foreach (['missing' => [], 'blank' => ['actor_name' => '   ']] as $case => $body) {
                $response = $this->json($this->method($route), $this->url($route, $fixtures), $body);

                $this->assertSame(422, $response->status(), "{$key} ({$case} actor_name) must be rejected with 422, got {$response->status()}");
                $this->assertArrayHasKey('actor_name', (array) $response->json('errors'), "{$key} ({$case}) must report an actor_name error");
            }
            $this->assertSame($auditRows, AuditEntry::count(), "{$key} wrote an audit row without a name");
            $checked[] = $key;
        }

        $this->assertContains('admin.media.edit', $checked);
        $this->assertContains('admin.security.logout-all', $checked);
        $this->assertGreaterThanOrEqual(18, count($checked), 'route sweep found suspiciously few mutating routes');
    }

    public function test_actor_name_exemptions_are_real_non_get_routes(): void
    {
        $routes = $this->adminRoutes();
        foreach (array_keys(self::ACTOR_NAME_EXEMPT) as $name) {
            $this->assertArrayHasKey($name, $routes, "stale exemption {$name}");
            $this->assertTrue($this->isMutating($routes[$name]), "exemption {$name} is not a mutating route");
        }
    }

    public function test_every_super_admin_route_is_forbidden_to_the_editor(): void
    {
        $fixtures = $this->fixtures();
        $editor = Account::factory()->editor()->create();
        $routes = $this->adminRoutes();

        foreach (self::EDITOR_ROUTES as $name) {
            $this->assertArrayHasKey($name, $routes, "EDITOR_ROUTES lists {$name}, which does not exist");
        }

        $superOnly = array_diff_key($routes, array_flip(self::EDITOR_ROUTES));
        foreach (['admin.pages.index', 'admin.system.show', 'admin.audit.index', 'admin.settings.edit', 'admin.security.edit'] as $name) {
            $this->assertArrayHasKey($name, $superOnly);
        }

        foreach ($superOnly as $key => $route) {
            $this->assertContains('role:super_admin', $route->gatherMiddleware(), "{$key} is not in EDITOR_ROUTES, so it must be in the role:super_admin group");

            $this->login($editor->fresh());
            $auditRows = AuditEntry::count();
            $response = $this->json($this->method($route), $this->url($route, $fixtures), ['actor_name' => 'Editor Person']);

            $this->assertSame(403, $response->status(), "editor must get 403 on {$key} ({$this->method($route)}), got {$response->status()}");
            $this->assertSame($auditRows, AuditEntry::count(), "{$key} wrote an audit row for a forbidden editor request");
        }
    }
}
