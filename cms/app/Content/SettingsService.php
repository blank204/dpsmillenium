<?php

namespace App\Content;

use App\Audit\AuditLogger;
use App\Models\CalendarType;
use App\Models\Grade;
use App\Models\NewsletterSeries;
use App\Models\Setting;
use App\Support\Actor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SettingsService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function snapshot(): array
    {
        return [
            'homepage_count' => collect(Section::cases())->mapWithKeys(fn ($s) => [$s->value => (int) Setting::get('homepage_count.'.$s->value)])->all(),
            'academic_year_start_month' => (int) Setting::get('academic_year_start_month'),
            'grades' => Grade::ordered()->get(['id', 'label', 'position', 'hidden'])->toArray(),
            'calendar_types' => CalendarType::orderBy('id')->get(['id', 'name', 'hidden'])->toArray(),
            'newsletter_series' => NewsletterSeries::orderBy('id')->get(['id', 'name', 'hidden'])->toArray(),
        ];
    }

    public function update(array $input, Actor $actor): void
    {
        $valid = Validator::make($input, [
            'homepage_count' => ['required', 'array'],
            'homepage_count.info' => ['required', 'integer', 'between:1,20'],
            'homepage_count.circular' => ['required', 'integer', 'between:1,20'],
            'homepage_count.event' => ['required', 'integer', 'between:1,20'],
            'homepage_count.newsletter' => ['required', 'integer', 'between:1,20'],
            'academic_year_start_month' => ['required', 'integer', 'between:1,12'],
            'grades' => ['required', 'array', 'min:1'],
            'grades.*.id' => ['nullable', 'integer', 'exists:grades,id'],
            'grades.*.label' => ['required', 'string', 'max:30'],
            'grades.*.position' => ['required', 'integer', 'min:0'],
            'grades.*.hidden' => ['nullable', 'boolean'],
            'calendar_types' => ['nullable', 'array'],
            'calendar_types.*.id' => ['nullable', 'integer', 'exists:calendar_types,id'],
            'calendar_types.*.name' => ['required', 'string', 'max:40'],
            'calendar_types.*.hidden' => ['nullable', 'boolean'],
            'newsletter_series' => ['nullable', 'array'],
            'newsletter_series.*.id' => ['nullable', 'integer', 'exists:newsletter_series,id'],
            'newsletter_series.*.name' => ['required', 'string', 'max:80'],
            'newsletter_series.*.hidden' => ['nullable', 'boolean'],
        ])->validate();

        foreach (['grades' => 'label', 'calendar_types' => 'name', 'newsletter_series' => 'name'] as $list => $field) {
            $names = array_map(fn ($r) => mb_strtolower(trim($r[$field])), $valid[$list] ?? []);
            if (count($names) !== count(array_unique($names))) {
                throw ValidationException::withMessages([$list => 'Two entries have the same name. Each must be different.']);
            }
        }

        DB::transaction(function () use ($valid, $actor) {
            $before = $this->snapshot();

            foreach ($valid['homepage_count'] as $section => $count) {
                Setting::put('homepage_count.'.$section, (int) $count);
            }
            Setting::put('academic_year_start_month', (int) $valid['academic_year_start_month']);

            // Two-phase rename avoids unique-key collisions when labels are swapped.
            $this->saveRows(Grade::class, $valid['grades'], fn ($r) => ['label' => trim($r['label']), 'position' => (int) $r['position'], 'hidden' => (bool) ($r['hidden'] ?? false)], 'label');
            $this->saveRows(CalendarType::class, $valid['calendar_types'] ?? [], fn ($r) => ['name' => trim($r['name']), 'hidden' => (bool) ($r['hidden'] ?? false)], 'name');
            $this->saveRows(NewsletterSeries::class, $valid['newsletter_series'] ?? [], fn ($r) => ['name' => trim($r['name']), 'hidden' => (bool) ($r['hidden'] ?? false)], 'name');

            $this->audit->record('settings.updated', $actor, null, $before, $this->snapshot());
        });
    }

    /** @param class-string<\Illuminate\Database\Eloquent\Model> $model */
    private function saveRows(string $model, array $rows, callable $map, string $uniqueField): void
    {
        foreach ($rows as $row) {
            if (! empty($row['id'])) {
                $model::whereKey($row['id'])->update([$uniqueField => '__tmp_'.$row['id']]);
            }
        }
        foreach ($rows as $row) {
            if (! empty($row['id'])) {
                $model::whereKey($row['id'])->update($map($row));
            } else {
                $model::create($map($row));
            }
        }
    }
}
