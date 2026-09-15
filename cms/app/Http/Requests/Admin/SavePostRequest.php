<?php

namespace App\Http\Requests\Admin;

use App\Content\PublishOptions;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Validator;

class SavePostRequest extends ActorRequest
{
    protected function fieldRules(): array
    {
        return [
            'intent' => ['required', 'in:draft,publish'],
            'title' => ['required', 'string', 'max:'.config('cms.limits.title')],
            'summary' => ['nullable', 'string', 'max:'.config('cms.limits.summary')],
            'blocks_json' => ['nullable', 'string', 'json'],
            'grades' => ['nullable', 'array'],
            'grades.mode' => ['nullable', 'in:none,all,range,specific'],
            'grades.from' => ['nullable', 'integer'],
            'grades.to' => ['nullable', 'integer'],
            'grades.ids' => ['nullable', 'array'],
            'grades.ids.*' => ['integer'],
            'event_date_start' => ['nullable', 'date_format:Y-m-d'],
            'event_date_end' => ['nullable', 'date_format:Y-m-d'],
            'cover_media_id' => ['nullable', 'integer'],
            'newsletter_series_id' => ['nullable', 'integer'],
            'newsletter_academic_year' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
            'newsletter_term' => ['nullable', 'string', 'max:40'],
            'newsletter_pdf_media_id' => ['nullable', 'integer'],
            'newsletter_link' => ['nullable', 'string', 'max:2000'],
            'publish_when' => ['nullable', 'in:now,schedule'],
            'publish_at' => ['nullable', 'required_if:publish_when,schedule', 'date_format:Y-m-d\TH:i'],
            'remove_on' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'pin' => ['nullable', 'boolean'],
            'pinned_until' => ['nullable', 'required_if:pin,1', 'date_format:Y-m-d\TH:i'],
            'small_fix' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'publish_at.required_if' => 'Choose the date and time to publish.',
            'pinned_until.required_if' => 'Pinned posts need an "unpin on" date.',
            'blocks_json.json' => 'The content could not be read. Reload the page and try again.',
        ];
    }

    private function dt(?string $value): ?CarbonImmutable
    {
        return $value ? CarbonImmutable::createFromFormat('Y-m-d\TH:i', $value, config('app.timezone'))->setSecond(0) : null;
    }

    public function postData(): array
    {
        $v = $this->validated();

        return [
            'title' => $v['title'],
            'summary' => $v['summary'] ?? null,
            'blocks' => isset($v['blocks_json']) ? (array) json_decode($v['blocks_json'], true) : [],
            'grades' => $v['grades'] ?? ['mode' => 'none'],
            'event_date_start' => $v['event_date_start'] ?? null,
            'event_date_end' => $v['event_date_end'] ?? null,
            'cover_media_id' => $v['cover_media_id'] ?? null,
            'newsletter_series_id' => $v['newsletter_series_id'] ?? null,
            'newsletter_academic_year' => $v['newsletter_academic_year'] ?? null,
            'newsletter_term' => $v['newsletter_term'] ?? null,
            'newsletter_pdf_media_id' => $v['newsletter_pdf_media_id'] ?? null,
            'newsletter_link' => $v['newsletter_link'] ?? null,
        ];
    }

    public function publishOptions(): PublishOptions
    {
        $v = $this->validated();

        return new PublishOptions(
            publishAt: ($v['publish_when'] ?? 'now') === 'schedule' ? $this->dt($v['publish_at'] ?? null) : null,
            removeOn: $this->dt($v['remove_on'] ?? null),
            pinnedUntil: $this->boolean('pin') ? $this->dt($v['pinned_until'] ?? null) : null,
            smallFix: $this->boolean('small_fix'),
        );
    }
}
