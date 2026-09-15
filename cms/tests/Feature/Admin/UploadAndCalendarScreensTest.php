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

        $source = MediaAsset::findOrFail($id);
        $sourceVariant = Storage::disk('media')->get($source->dir().'/800.jpg');

        $newId = $this->postJson(route('admin.media.edit', $id), ['actor_name' => '  Ms. Priya ', 'crop' => ['x' => 0, 'y' => 0, 'w' => 500, 'h' => 500], 'max_width' => 300])
            ->assertOk()->json('id');

        // Non-destructive: the original (possibly used by live posts) is untouched;
        // the cropped copy only reaches the site when a post is saved with it.
        $this->assertNotSame($id, $newId);
        $this->assertSame(1, $source->fresh()->rev);
        $this->assertNull($source->fresh()->crop);
        $this->assertSame($sourceVariant, Storage::disk('media')->get($source->dir().'/800.jpg'));

        $copy = MediaAsset::findOrFail($newId);
        $this->assertSame(['x' => 0, 'y' => 0, 'w' => 500, 'h' => 500], $copy->crop);
        [$w, $h] = getimagesizefromstring(Storage::disk('media')->get($copy->dir().'/1600.jpg'));
        $this->assertSame([300, 300], [$w, $h]);

        $entry = \App\Models\AuditEntry::where('action', 'media.cropped')->sole();
        $this->assertSame('Ms. Priya', $entry->actor_name);
        $this->assertSame('MediaAsset', $entry->target_type);
        $this->assertSame($newId, (int) $entry->target_id);
        $this->assertStringContainsString('"source_media_id":'.$id, (string) $entry->after_json);
    }

    public function test_media_edit_requires_actor_name(): void
    {
        $id = $this->post(route('admin.media.upload'), ['kind' => 'image', 'file' => Fixtures::uploaded(Fixtures::jpegWithExif(600, 400), 'a.jpg')])->json('id');

        $this->postJson(route('admin.media.edit', $id), ['crop' => ['x' => 0, 'y' => 0, 'w' => 100, 'h' => 100]])
            ->assertStatus(422)->assertJsonValidationErrors('actor_name');
        $this->postJson(route('admin.media.edit', $id), ['actor_name' => '   ', 'max_width' => 100])
            ->assertStatus(422)->assertJsonValidationErrors('actor_name');

        $this->assertSame(1, MediaAsset::count());
        $this->assertFalse(\App\Models\AuditEntry::where('action', 'media.cropped')->exists());
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

    public function test_calendar_grid_has_no_extra_trailing_week_when_next_month_starts_sunday(): void
    {
        // October 2026 ends on a Saturday (Oct 31) and November 2026 starts on a Sunday
        // (Nov 1). A naive "next month's start -> endOfWeek(Saturday)" boundary would
        // therefore render one whole superfluous week of November at the end of the grid.
        $html = $this->get(route('admin.calendar.index', ['year' => 2026, 'month' => 10]))->assertOk()->getContent();

        preg_match_all('/<tr>(.*?)<\/tr>/s', $html, $rows);
        $bodyRows = array_slice($rows[1], 1); // drop the Sun..Sat header row
        $this->assertNotEmpty($bodyRows);

        foreach ($bodyRows as $row) {
            preg_match('/<td[^>]*>\s*<span>(\d+)<\/span>/', $row, $firstCell);
            $this->assertNotSame('1', $firstCell[1] ?? null, 'a calendar row must not start on Nov 1 — the grid ran one week too long');
        }
    }
}
