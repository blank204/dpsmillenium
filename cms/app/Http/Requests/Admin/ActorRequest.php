<?php

namespace App\Http\Requests\Admin;

use App\Support\Actor;
use Illuminate\Foundation\Http\FormRequest;

abstract class ActorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('actor_name'))) {
            $this->merge(['actor_name' => trim($this->input('actor_name'))]);
        }
    }

    public function rules(): array
    {
        return array_merge(
            ['actor_name' => ['required', 'string', 'min:1', 'max:'.config('cms.limits.actor_name')]],
            $this->fieldRules(),
        );
    }

    public function messages(): array
    {
        return ['actor_name.required' => 'Type your name before saving. This is recorded in the change log.'];
    }

    abstract protected function fieldRules(): array;

    public function actor(): Actor
    {
        return Actor::fromRequest($this, $this->validated('actor_name'));
    }
}
