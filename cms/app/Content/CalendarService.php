<?php

namespace App\Content;

use App\Audit\AuditLogger;
use App\Models\CalendarEntry;
use App\Support\Actor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CalendarService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(array $data, Actor $actor): CalendarEntry
    {
        return DB::transaction(function () use ($data, $actor) {
            $entry = new CalendarEntry();
            $this->fill($entry, $data);
            $entry->save();
            $this->audit->record('calendar.created', $actor, $entry, null, $entry->snapshot());

            return $entry;
        });
    }

    public function update(CalendarEntry $entry, array $data, Actor $actor): CalendarEntry
    {
        return DB::transaction(function () use ($entry, $data, $actor) {
            $before = $entry->snapshot();
            $this->fill($entry, $data);
            $entry->save();
            $this->audit->record('calendar.updated', $actor, $entry, $before, $entry->snapshot());

            return $entry;
        });
    }

    public function delete(CalendarEntry $entry, Actor $actor): void
    {
        DB::transaction(function () use ($entry, $actor) {
            $before = $entry->snapshot();
            $entry->delete();
            $this->audit->record('calendar.deleted', $actor, $entry, $before, null);
        });
    }

    private function fill(CalendarEntry $entry, array $data): void
    {
        $v = Validator::make($data, [
            'title' => ['required', 'string', 'max:'.config('cms.limits.title')],
            'calendar_type_id' => ['required', 'integer', 'exists:calendar_types,id'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'note' => ['nullable', 'string', 'max:'.config('cms.limits.calendar_note')],
        ], [
            'end_date.after_or_equal' => 'The end date must be on or after the start date.',
        ]);
        $valid = $v->validate();

        try {
            $grades = GradeScope::fromInput((array) ($data['grades'] ?? ['mode' => 'none']))->toStorage();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['grades' => $e->getMessage()]);
        }

        $entry->title = trim($valid['title']);
        $entry->calendar_type_id = (int) $valid['calendar_type_id'];
        $entry->start_date = $valid['start_date'];
        $entry->end_date = $valid['end_date'] ?? null;
        $entry->note = isset($valid['note']) && trim($valid['note']) !== '' ? trim($valid['note']) : null;
        $entry->grade_mode = $grades['mode'];
        $entry->grade_ids = $grades['ids'];
    }
}
