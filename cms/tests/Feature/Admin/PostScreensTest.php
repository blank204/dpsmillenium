<?php

namespace Tests\Feature\Admin;

use App\Content\Section;
use App\Models\Account;
use App\Models\AuditEntry;
use App\Models\HtmlPage;
use App\Models\Post;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

    public function test_actor_name_is_not_flashed_after_failed_submit(): void
    {
        $this->post(route('admin.posts.store', 'info'), $this->form(['title' => '']))->assertSessionHasErrors('title');

        $this->assertNull(session('_old_input.actor_name'));
    }

    public function test_blocks_data_is_hex_escaped_against_script_breakout(): void
    {
        $post = Post::factory()->draft()->create([
            'blocks' => [['type' => 'table', 'version' => 1, 'has_header' => false, 'rows' => [['<!--<script>', 'b']]]],
        ]);

        $html = $this->get(route('admin.posts.edit', $post))->assertOk()->getContent();

        preg_match('/id="blocks-data">(.*?)<\/script>/s', $html, $m);
        $this->assertNotEmpty($m, 'could not find the #blocks-data script tag');
        $this->assertStringContainsString('\\u003C', $m[1]);
        $this->assertStringNotContainsString('<!--<script>', $m[1]);
    }

    public function test_duplicate_strips_html_page_blocks_for_editor_but_keeps_for_super_admin(): void
    {
        $page = HtmlPage::create(['token' => Str::random(32), 'title' => 'Seating', 'bytes' => 10]);
        $blocks = [
            ['type' => 'text', 'version' => 1, 'html' => '<p>Hi</p>'],
            ['type' => 'html_page', 'version' => 1, 'html_page_id' => $page->id, 'mode' => 'link'],
        ];
        $original = Post::factory()->published()->create(['title' => 'Has custom page', 'blocks' => $blocks]);

        $this->post(route('admin.posts.duplicate', $original), ['actor_name' => 'A'])
            ->assertRedirect()
            ->assertSessionHas('status', fn ($status) => str_contains($status, 'Custom HTML page blocks were not copied'));
        $editorCopy = Post::where('title', 'Copy of Has custom page')->firstOrFail();
        $this->assertFalse(collect($editorCopy->blocks)->contains(fn ($b) => $b['type'] === 'html_page'));

        $superAdmin = Account::factory()->superAdmin()->create();
        $this->actingAs($superAdmin)->withSession(['session_version' => $superAdmin->session_version]);
        $this->post(route('admin.posts.duplicate', $original), ['actor_name' => 'B'])->assertRedirect();
        $superCopy = Post::where('title', 'Copy of Has custom page')->where('id', '!=', $editorCopy->id)->firstOrFail();
        $this->assertTrue(collect($superCopy->blocks)->contains(fn ($b) => $b['type'] === 'html_page'));
    }

    public function test_preview_of_existing_post_does_not_reject_its_own_html_page_block(): void
    {
        $page = HtmlPage::create(['token' => Str::random(32), 'title' => 'Seating', 'bytes' => 10]);
        $blocks = [['type' => 'html_page', 'version' => 1, 'html_page_id' => $page->id, 'mode' => 'link']];
        $post = Post::factory()->published()->create(['blocks' => $blocks]);

        $this->post(route('admin.posts.preview'), [
            'section' => $post->section->value,
            'title' => $post->title,
            'post_id' => $post->id,
            'blocks_json' => json_encode($blocks),
        ])->assertOk()->assertDontSee('This post has problems');
    }
}
