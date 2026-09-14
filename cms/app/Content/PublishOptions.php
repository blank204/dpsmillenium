<?php

namespace App\Content;

use Carbon\CarbonImmutable;

final class PublishOptions
{
    public function __construct(
        public readonly ?CarbonImmutable $publishAt = null,
        public readonly ?CarbonImmutable $removeOn = null,
        public readonly ?CarbonImmutable $pinnedUntil = null,
        public readonly bool $smallFix = false,
    ) {}
}
