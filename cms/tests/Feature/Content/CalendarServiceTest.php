<?php

namespace Tests\Feature\Content;

use App\Content\CalendarService;
use App\Models\AuditEntry;
use App\Models\CalendarEntry;
use App\Models\CalendarType;
use App\Support\Actor;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CalendarServiceTest extends TestCase
{
    use RefreshDatabase;

    private function actor(): Actor
    {
        return new Actor('Ms. Priya', 1, 'editor', '1.1.1.1', 'UA', 's');
    }

    private function holiday(): int
    {
        return CalendarType::where('name', 'Holiday')->value('id');
    }

    public function test_create_update_delete_with_audit(): void
    {
        $service = app(CalendarService::class);
        $entry = $service->create(['title' => 'Eid holidays', 'calendar_type_id' => $this->holiday(), 'start_date' => '2027-03-16', 'end_date' => '2027-03-20', 'grades' => ['mode' => 'all'], 'note' => null], $this->actor());

        $this->assertSame('2027-03-20', $entry->end_date->format('Y-m-d'));
        $service->update($entry, ['title' => 'Eid al-Fitr holidays', 'calendar_type_id' => $this->holiday(), 'start_date' => '2027-03-16', 'end_date' => null, 'grades' => ['mode' => 'none'], 'note' => 'Tentative'], $this->actor());
        $this->assertNull($entry->fresh()->end_date);
        $service->delete($entry->fresh(), $this->actor());

        $this->assertSame(['calendar.created', 'calendar.updated', 'calendar.deleted'], AuditEntry::orderBy('id')->pluck('action')->all());
        $this->assertSame(0, CalendarEntry::count());
    }

    public function test_validation(): void
    {
        $service = app(CalendarService::class);
        foreach ([
            ['title' => '', 'calendar_type_id' => $this->holiday(), 'start_date' => '2027-01-01'],
            ['title' => 'X', 'calendar_type_id' => 999, 'start_date' => '2027-01-01'],
            ['title' => 'X', 'calendar_type_id' => $this->holiday(), 'start_date' => 'not-a-date'],
            ['title' => 'X', 'calendar_type_id' => $this->holiday(), 'start_date' => '2027-01-05', 'end_date' => '2027-01-01'],
            ['title' => 'X', 'calendar_type_id' => $this->holiday(), 'start_date' => '2027-01-05', 'note' => str_repeat('a', 501)],
        ] as $bad) {
            try {
                $service->create($bad, $this->actor());
                $this->fail('accepted: '.json_encode($bad));
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_overlapping_scope_includes_ranges_crossing_month_edges(): void
    {
        $service = app(CalendarService::class);
        $service->create(['title' => 'Winter break', 'calendar_type_id' => $this->holiday(), 'start_date' => '2026-12-28', 'end_date' => '2027-01-03'], $this->actor());
        $service->create(['title' => 'Single day', 'calendar_type_id' => $this->holiday(), 'start_date' => '2027-01-15'], $this->actor());
        $service->create(['title' => 'Feb', 'calendar_type_id' => $this->holiday(), 'start_date' => '2027-02-01'], $this->actor());

        $jan = CalendarEntry::overlapping(CarbonImmutable::parse('2027-01-01'), CarbonImmutable::parse('2027-02-01'))->pluck('title')->all();
        $this->assertEqualsCanonicalizing(['Winter break', 'Single day'], $jan);
    }
}
