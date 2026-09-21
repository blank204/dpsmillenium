<?php

namespace App\Http\Requests\Admin;

class CalendarEntryRequest extends ActorRequest
{
    protected function fieldRules(): array
    {
        return [
            'title' => ['required', 'string'],
            'calendar_type_id' => ['required', 'integer'],
            'start_date' => ['required', 'string'],
            'end_date' => ['nullable', 'string'],
            'grades' => ['nullable', 'array'],
            'note' => ['nullable', 'string'],
        ];
    }

    public function entryData(): array
    {
        return collect($this->validated())->except('actor_name')->all();
    }
}
