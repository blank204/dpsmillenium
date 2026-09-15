<?php

namespace Tests\Feature\Admin;

use App\Content\Section;
use App\Models\Account;
use App\Models\AuditEntry;
use App\Models\Post;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostScreensTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-09-15 10:00:00'));
        $editor = Account::factory()->editor()->create();
        $this->actingAs($editor)->withSession(['session_version' => $editor->session_version]);
    }

    private function form(array $overrides = []): array
    {
        return array_merge([
            'actor_name' => 'Ms. Priya',
            'intent' => 'publish',
            'title' => 'Welcome back message',
            'summary' => '',
            'blocks_json' => json_encode([['type' => 'text', 'html' => '<p>Welcome back!</p>']]),
            'grades' => ['mode' => 'all'],
            'publish_when' => 'now',
        ], $overrides);
    }

    public function test_dashboard_and_screens_render(): void
    {
        Post::factory()->scheduled(now()->addDay())->create(['title' => 'Scheduled one']);
        Post::factory()->published()->create(['title' => 'Pinned one', 'pinned_until' => now()->addDays(2)]);
        Post::factory()->draft()->create(['title' => 'Draft one']);

        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Scheduled one')->assertSee('Pinned one')->assertSee('Draft one');
        $this->get(route('admin.posts.choose'))->assertOk()->assertSee(route('admin.posts.create', 'circular'), false);
        $this->get(route('admin.posts.create', 'circular'))->assertOk()->assertSee('name="actor_name"', false);
        $this->get(route('admin.posts.create', 'nonsense'))->assertNotFound();
    }

    public function test_publish_new_post_now(): void
    {
        $this->post(route('admin.posts.store', 'info'), $this->form())->assertRedirect();

        $post = Post::firstOrFail();
        $this->assertSame('live', $post->adminStatus());
        $this->assertSame('All grades', $post->gradeScope()->label());
        $this->assertTrue(AuditEntry::where('action', 'post.published')->where('actor_name', 'Ms. Priya')->exists());
    }

    public function test_save_draft_then_schedule(): void
    {
        $this->post(route('admin.posts.store', 'circular'), $this->form(['intent' => 'draft']));
        $post = Post::firstOrFail();
        $this->assertSame('draft', $post->adminStatus());

        $this->put(route('admin.posts.update', $post), $this->form(['publish_when' => 'schedule', 'publish_at' => '2026-09-20T07:00']))
            ->assertRedirect(route('admin.posts.edit', $post));
        $this->assertSame('2026-09-20 07:00:00', $post->fresh()->publish_at->format('Y-m-d H:i:s'));
    }

    public function test_name_is_required_and_never_echoed_back(): void
    {
        $this->post(route('admin.posts.store', 'info'), $this->form(['actor_name' => '']))->assertSessionHasErrors('actor_name');
        $this->assertSame(0, Post::count());

        $this->post(route('admin.posts.store', 'info'), $this->form(['actor_name' => 'Typed Name', 'title' => '']))->assertSessionHasErrors('title');
        $page = $this->get(route('admin.posts.create', 'info'))->getContent();
        $this->assertStringNotContainsString('Typed Name', $page);
    }

    public function test_edit_live_post_with_small_fix(): void
    {
        $post = Post::factory()->published(now()->subDays(3))->create();
        $sort = $post->sort_at;

        $this->put(route('admin.posts.update', $post), $this->form(['small_fix' => '1']))->assertRedirect();

        $this->assertEquals($sort, $post->fresh()->sort_at);
        $this->assertNotNull($post->fresh()->updated_at_public);
    }

    public function test_pin_checkbox_requires_unpin_date(): void
    {
        $this->post(route('admin.posts.store', 'info'), $this->form(['pin' => '1', 'pinned_until' => '']))->assertSessionHasErrors('pinned_until');
    }

    public function test_block_errors_are_returned_to_form(): void
    {
        $this->post(route('admin.posts.store', 'info'), $this->form(['blocks_json' => json_encode([['type' => 'link', 'label' => 'x', 'url' => 'javascript:1']])]))
            ->assertSessionHasErrors('blocks.0');
        $this->post(route('admin.posts.store', 'info'), $this->form(['blocks_json' => '{not json']))
            ->assertSessionHasErrors('blocks_json');
    }

    public function test_delete_duplicate_unschedule_unpin(): void
    {
        $live = Post::factory()->published()->create(['title' => 'Original', 'pinned_until' => now()->addDay()]);
        $scheduled = Post::factory()->scheduled(now()->addDay())->create();

        $this->post(route('admin.posts.duplicate', $live), ['actor_name' => 'A'])->assertRedirect();
        $this->assertTrue(Post::where('title', 'Copy of Original')->where('status', 'draft')->exists());

        $this->post(route('admin.posts.unpin', $live), ['actor_name' => 'A'])->assertRedirect();
        $this->assertNull($live->fresh()->pinned_until);

        $this->post(route('admin.posts.unschedule', $scheduled), ['actor_name' => 'A'])->assertRedirect();
        $this->assertSame('draft', $scheduled->fresh()->adminStatus());

        $this->delete(route('admin.posts.destroy', $live), ['actor_name' => ''])->assertSessionHasErrors('actor_name');
        $this->delete(route('admin.posts.destroy', $live), ['actor_name' => 'A'])->assertRedirect(route('admin.posts.index'));
        $this->assertSoftDeleted($live);
    }

    public function test_index_filters_by_section_and_status(): void
    {
        Post::factory()->section(Section::Circular)->published()->create(['title' => 'Live circular']);
        Post::factory()->section(Section::Info)->draft()->create(['title' => 'Draft info']);

        $this->get(route('admin.posts.index', ['section' => 'circular']))->assertSee('Live circular')->assertDontSee('Draft info');
        $this->get(route('admin.posts.index', ['status' => 'draft']))->assertSee('Draft info')->assertDontSee('Live circular');
        $this->get(route('admin.posts.index', ['q' => 'circular']))->assertSee('Live circular');
    }

    public function test_preview_renders_without_saving(): void
    {
        $this->post(route('admin.posts.preview'), $this->form(['section' => 'info', 'title' => 'Preview title']))
            ->assertOk()->assertSee('Preview title')->assertSee('Welcome back!');
        $this->assertSame(0, Post::count());
    }

    public function test_editor_cannot_open_deleted_post_for_editing(): void
    {
        $post = Post::factory()->published()->create();
        $post->delete();
        $this->get(route('admin.posts.edit', $post->id))->assertNotFound();
    }
}
