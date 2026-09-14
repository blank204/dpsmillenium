<?php

namespace Tests\Support;

use App\Http\Requests\Admin\ActorRequest;

class ProbeActorRequest extends ActorRequest
{
    protected function fieldRules(): array
    {
        return [];
    }
}
