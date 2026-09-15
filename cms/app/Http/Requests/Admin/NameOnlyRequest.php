<?php

namespace App\Http\Requests\Admin;

class NameOnlyRequest extends ActorRequest
{
    protected function fieldRules(): array
    {
        return [];
    }
}
