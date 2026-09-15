<?php

namespace Tests\Feature\Admin;

use App\Audit\AuditLogger;
use App\Audit\Diff;
use App\Content\PostService;
use App\Content\PublishOptions;
use App\Content\Section;
use App\Models\Account;
use App\Models\AuditEntry;
use App\Models\Post;
use App\Support\Actor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperAdminAuditTest extends TestCase
{
    use RefreshDatabase;

    private function as(string $role): Account
    {
        $a = $role === 'super' ? Account::factory()->superAdmin()->create() : Account::factory()->editor()->create();
        $this->actingAs($a)->withSession(['session_version' => $a->session_version]);
        return $a;
    }

    public function test_editor_is_forbidden_from_super_admin_screens(): void
    {
        $this->as('editor');
        $post = Post::factory()->published()->create();
        $this->get(route('admin.audit.index'))->assertForbidden();
        $this->get(route('admin.posts.versions', $post))->assertForbidden();
        $this->get(route('admin.security.edit'))->assertForbidden();
        $post->delete();
        $this->post(route('admin.recycle.restore', $post->id), ['actor_name' => 'X'])->assertForbidden();
    }

    public function test_change_log_lists_filters_and_shows_diff(): void
    {
        $this->as('super');
        $actor = new Actor('Ms. Priya', 1, 'editor', '10.1.1.1', 'Firefox', 's1');
        app(AuditLogger::class)->record('settings.updated', $actor, null, ['homepage_count.info' => 5, 'same' => 1], ['homepage_count.info' => 6, 'same' => 1]);
        app(AuditLogger::class)->record('calendar.created', new Actor('Mr. Khan', 1, 'editor', '10.1.1.2', 'Chrome', 's2'), null, null, ['title' => 'Exam']);

        $this->get(route('admin.audit.index'))->assertOk()->assertSee('Ms. Priya')->assertSee('Mr. Khan');
        $this->get(route('admin.audit.index', ['name' => 'Khan']))->assertSee('Mr. Khan')->assertDontSee('Ms. Priya');
        $this->get(route('admin.audit.index', ['action' => 'settings.updated']))->assertSee('Ms. Priya')->assertDontSee('Mr. Khan');

        $entry = AuditEntry::where('action', 'settings.updated')->first();
        $this->get(route('admin.audit.show', $entry))->assertOk()->assertSee('homepage_count.info')->assertSee('10.1.1.1')->assertSee('Firefox')->assertDontSee('>same<', false);
    }

    public function test_diff_only_reports_changes(): void
    {
        $this->assertSame(['b' => ['before' => 1, 'after' => 2], 'c' => ['before' => null, 'after' => 3]], Diff::between(['a' => 1, 'b' => 1], ['a' => 1, 'b' => 2, 'c' => 3]));
        $this->assertSame(['a' => ['before' => 1, 'after' => null]], Diff::between(['a' => 1], null));
    }

    public function test_verify_integrity_button(): void
    {
        $this->as('super');
        app(AuditLogger::class)->record('settings.updated', Actor::system());
        $this->post(route('admin.audit.verify'))->assertRedirect()->assertSessionHas('status', fn ($s) => str_contains($s, 'intact'));

        DB::table('audit_entries')->where('action', 'settings.updated')->update(['actor_name' => 'forged']);
        $this->post(route('admin.audit.verify'))->assertSessionHas('status', fn ($s) => str_contains($s, 'ALTERED'));
    }

    public function test_versions_list_and_restore(): void
    {
        $this->as('super');
        $service = app(PostService::class);
        $actor = new Actor('A', 1, 'editor', null, null, null);
        $post = $service->saveDraft(null, Section::Info, ['title' => 'V1', 'blocks' => [['type' => 'text', 'html' => '<p>x</p>']]], $actor, false);
        $post = $service->publish($post, ['title' => 'V2', 'blocks' => [['type' => 'text', 'html' => '<p>x</p>']]], new PublishOptions(), $actor, false);

        $this->get(route('admin.posts.versions', $post))->assertOk()->assertSee('V1')->assertSee('V2');

        $v1 = $post->versions()->where('version', 1)->first();
        $this->post(route('admin.posts.versions.restore', [$post, $v1]), ['actor_name' => ''])->assertSessionHasErrors('actor_name');
        $this->post(route('admin.posts.versions.restore', [$post, $v1]), ['actor_name' => 'IT'])->assertRedirect(route('admin.posts.edit', $post));
        $this->assertSame('V1', $post->fresh()->title);
    }

    public function test_recycle_restore(): void
    {
        $this->as('super');
        $post = Post::factory()->published()->create();
        $post->delete();

        $this->post(route('admin.recycle.restore', $post->id), ['actor_name' => 'IT'])->assertRedirect(route('admin.recycle.index'));
        $this->assertFalse($post->fresh()->trashed());
        $this->assertTrue(AuditEntry::where('action', 'post.restored')->where('actor_name', 'IT')->exists());
    }
}
