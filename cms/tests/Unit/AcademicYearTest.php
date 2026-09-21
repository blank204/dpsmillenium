<?php

namespace Tests\Unit;

use App\Content\AcademicYear;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class AcademicYearTest extends TestCase
{
    public function test_april_start(): void
    {
        $this->assertSame('2026-27', AcademicYear::forDate(CarbonImmutable::parse('2026-04-01'), 4));
        $this->assertSame('2025-26', AcademicYear::forDate(CarbonImmutable::parse('2026-03-31 23:59'), 4));
        $this->assertSame('2026-27', AcademicYear::forDate(CarbonImmutable::parse('2027-01-15'), 4));
    }

    public function test_september_start(): void
    {
        $this->assertSame('2025-26', AcademicYear::forDate(CarbonImmutable::parse('2026-08-31'), 9));
        $this->assertSame('2026-27', AcademicYear::forDate(CarbonImmutable::parse('2026-09-01'), 9));
    }

    public function test_century_rollover_label(): void
    {
        $this->assertSame('2099-00', AcademicYear::forDate(CarbonImmutable::parse('2099-05-01'), 4));
    }

    public function test_bounds(): void
    {
        [$start, $end] = AcademicYear::bounds('2026-27', 4);
        $this->assertSame('2026-04-01 00:00:00', $start->format('Y-m-d H:i:s'));
        $this->assertSame('2027-04-01 00:00:00', $end->format('Y-m-d H:i:s'));
    }
}
