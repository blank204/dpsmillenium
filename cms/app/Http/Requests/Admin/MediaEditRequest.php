<?php

namespace App\Http\Requests\Admin;

class MediaEditRequest extends ActorRequest
{
    protected function fieldRules(): array
    {
        return [
            'crop' => ['nullable', 'array'],
            'crop.x' => ['required_with:crop', 'integer', 'min:0'],
            'crop.y' => ['required_with:crop', 'integer', 'min:0'],
            'crop.w' => ['required_with:crop', 'integer', 'min:1'],
            'crop.h' => ['required_with:crop', 'integer', 'min:1'],
            'max_width' => ['nullable', 'integer', 'min:50', 'max:2560'],
        ];
    }
}
