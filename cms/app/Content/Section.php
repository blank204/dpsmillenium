<?php

namespace App\Content;

enum Section: string
{
    case Info = 'info';
    case Event = 'event';
    case Circular = 'circular';
    case Newsletter = 'newsletter';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'Information corner',
            self::Event => 'Events corner',
            self::Circular => 'Academic circulars',
            self::Newsletter => 'Newsletter',
        };
    }

    public function pathSegment(): string
    {
        return match ($this) {
            self::Info => 'information',
            self::Event => 'events',
            self::Circular => 'circulars',
            self::Newsletter => 'newsletters',
        };
    }
}
