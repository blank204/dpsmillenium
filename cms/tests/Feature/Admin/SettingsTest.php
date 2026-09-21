<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\AuditEntry;
use App\Models\Grade;
use App\Models\NewsletterSeries;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $a = Account::factory()->superAdmin()->create();
        $this->actingAs($a)->withSession(['session_version' => $a->session_version]);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'actor_name' => 'IT',
            'homepage_count' => ['info' => 6, 'circular' => 4, 'event' => 8, 'newsletter' => 4],
            'academic_year_start_month' => 4,
            'grades' => Grade::ordered()->get()->map(fn ($g) => ['id' => $g->id, 'label' => $g->label, 'position' => $g->position, 'hidden' => $g->hidden ? 1 : 0])->all(),
            'calendar_types' => [],
            'newsletter_series' => [['name' => 'Kindergarten', 'hidden' => 0]],
        ], $overrides);
    }

    public function test_renders_and_updates_with_audit(): void
    {
        $this->get(route('admin.settings.edit'))->assertOk()->assertSee('Homepage');

        $this->put(route('admin.settings.update'), $this->payload())->assertRedirect(route('admin.settings.edit'));

        $this->assertSame(6, Setting::get('homepage_count.info'));
        $this->assertTrue(NewsletterSeries::where('name', 'Kindergarten')->exists());
        $entry = AuditEntry::where('action', 'settings.updated')->firstOrFail();
        $this->assertSame(5, $entry->beforeData()['homepage_count']['info']);
        $this->assertSame(6, $entry->afterData()['homepage_count']['info']);
    }

    public function test_rename_and_hide_grade_add_new(): void
    {
        $payload = $this->payload();
        $payload['grades'][0]['label'] = 'Nursery';
        $payload['grades'][1]['hidden'] = 1;
        $payload['grades'][] = ['label' => 'KG3', 'position' => 35, 'hidden' => 0];

        $this->put(route('admin.settings.update'), $payload)->assertRedirect();

        $this->assertTrue(Grade::where('label', 'Nursery')->exists());
        $this->assertTrue((bool) Grade::where('label', 'KG1')->value('hidden'));
        $this->assertSame(['Nursery', 'KG1', 'KG2', 'KG3'], array_slice(Grade::ordered()->pluck('label')->all(), 0, 4));
    }

    public function test_swap_two_grade_labels_in_one_save(): void
    {
        $payload = $this->payload();
        // grades[1] = KG1, grades[2] = KG2 (both hit the unique index in one update).
        [$payload['grades'][1]['label'], $payload['grades'][2]['label']] = [$payload['grades'][2]['label'], $payload['grades'][1]['label']];

        $this->put(route('admin.settings.update'), $payload)->assertRedirect();

        $this->assertSame(['Pre-KG', 'KG2', 'KG1'], array_slice(Grade::ordered()->pluck('label')->all(), 0, 3));
    }

    public function test_validation(): void
    {
        $this->put(route('admin.settings.update'), $this->payload(['homepage_count' => ['info' => 0]]))->assertSessionHasErrors('homepage_count.info');
        $this->put(route('admin.settings.update'), $this->payload(['academic_year_start_month' => 13]))->assertSessionHasErrors('academic_year_start_month');
        $dup = $this->payload();
        $dup['grades'][1]['label'] = $dup['grades'][0]['label'];
        $this->put(route('admin.settings.update'), $dup)->assertSessionHasErrors('grades');
        $this->put(route('admin.settings.update'), $this->payload(['actor_name' => '']))->assertSessionHasErrors('actor_name');
    }

    public function test_editor_forbidden(): void
    {
        $e = Account::factory()->editor()->create();
        $this->actingAs($e)->withSession(['session_version' => $e->session_version]);
        $this->get(route('admin.settings.edit'))->assertForbidden();
    }
}
