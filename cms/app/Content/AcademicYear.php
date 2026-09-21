<?php

namespace App\Content;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

final class AcademicYear
{
    public static function forDate(CarbonInterface $date, ?int $startMonth = null): string
    {
        $startMonth ??= (int) Setting::get('academic_year_start_month', 4);
        $startYear = $date->month >= $startMonth ? $date->year : $date->year - 1;

        return sprintf('%d-%02d', $startYear, ($startYear + 1) % 100);
    }

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} start inclusive, end exclusive */
    public static function bounds(string $label, ?int $startMonth = null): array
    {
        if (! preg_match('/^(\d{4})-\d{2}$/', $label, $m)) {
            throw new InvalidArgumentException("Invalid academic year label: {$label}");
        }
        $startMonth ??= (int) Setting::get('academic_year_start_month', 4);
        $start = CarbonImmutable::create((int) $m[1], $startMonth, 1, 0, 0, 0);

        return [$start, $start->addYear()];
    }

    public static function current(): string
    {
        return self::forDate(CarbonImmutable::now());
    }
}
