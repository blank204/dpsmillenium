<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\CalendarEntry;
use App\Models\CalendarType;
use App\Models\MediaAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Fixtures;
use Tests\TestCase;

class UploadAndCalendarScreensTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('media');
        $editor = Account::factory()->editor()->create();
        $this->actingAs($editor)->withSession(['session_version' => $editor->session_version]);
    }

    public function test_upload_returns_json(): void
    {
        $this->post(route('admin.media.upload'), ['kind' => 'image', 'file' => Fixtures::uploaded(Fixtures::jpegWithExif(600, 400), 'a.jpg')])
            ->assertOk()->assertJsonStructure(['id', 'kind', 'original_name', 'preview_url']);
    }

    public function test_upload_rejection_is_422_with_message(): void
    {
        $this->post(route('admin.media.upload'), ['kind' => 'pdf', 'file' => Fixtures::uploaded(Fixtures::jpegWithExif(10, 10), 'x.pdf')])
            ->assertStatus(422)->assertJsonPath('message', fn ($m) => str_contains($m, 'not a PDF'));
    }

    public function test_media_edit_crops(): void
    {
        $id = $this->post(route('admin.media.upload'), ['kind' => 'image', 'file' => Fixtures::uploaded(Fixtures::jpegWithExif(1000, 500), 'a.jpg')])->json('id');

        $this->post(route('admin.media.edit', $id), ['crop' => ['x' => 0, 'y' => 0, 'w' => 500, 'h' => 500], 'max_width' => 300])
            ->assertOk()->assertJsonPath('id', $id);
        $this->assertSame(2, MediaAsset::find($id)->rev);
    }

    public function test_calendar_crud(): void
    {
        $type = CalendarType::where('name', 'Exam')->value('id');

        $this->get(route('admin.calendar.index', ['year' => 2026, 'month' => 10]))->assertOk();

        $this->post(route('admin.calendar.store'), ['actor_name' => '', 'title' => 'Half yearly', 'calendar_type_id' => $type, 'start_date' => '2026-10-04'])
            ->assertSessionHasErrors('actor_name');

        $this->post(route('admin.calendar.store'), ['actor_name' => 'Ms. Priya', 'title' => 'Half yearly', 'calendar_type_id' => $type, 'start_date' => '2026-10-04', 'end_date' => '2026-10-15', 'grades' => ['mode' => 'none']])
            ->assertRedirect(route('admin.calendar.index', ['year' => 2026, 'month' => 10]));
        $entry = CalendarEntry::firstOrFail();

        $this->get(route('admin.calendar.index', ['year' => 2026, 'month' => 10]))->assertSee('Half yearly');

        $this->put(route('admin.calendar.update', $entry), ['actor_name' => 'Ms. Priya', 'title' => 'Half yearly exams', 'calendar_type_id' => $type, 'start_date' => '2026-10-04'])
            ->assertRedirect();
        $this->assertSame('Half yearly exams', $entry->fresh()->title);

        $this->delete(route('admin.calendar.destroy', $entry), ['actor_name' => 'Ms. Priya'])->assertRedirect();
        $this->assertSoftDeleted($entry);
    }

    public function test_recycle_bin_lists_deleted_posts_for_editor(): void
    {
        $post = \App\Models\Post::factory()->published()->create(['title' => 'Binned']);
        $post->delete();
        $this->get(route('admin.recycle.index'))->assertOk()->assertSee('Binned');
    }
}
