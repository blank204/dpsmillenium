<?php

namespace Tests\Feature\Content;

use App\Content\GradeScope;
use App\Models\Grade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class GradeScopeTest extends TestCase
{
    use RefreshDatabase;

    private function id(string $label): int
    {
        return Grade::where('label', $label)->value('id');
    }

    public function test_none_has_no_label(): void
    {
        $scope = GradeScope::fromInput(['mode' => 'none']);
        $this->assertNull($scope->label());
        $this->assertSame(['mode' => 'none', 'ids' => []], $scope->toStorage());
    }

    public function test_all_grades(): void
    {
        $this->assertSame('All grades', GradeScope::fromInput(['mode' => 'all'])->label());
    }

    public function test_range_expands_in_position_order(): void
    {
        $scope = GradeScope::fromInput(['mode' => 'range', 'from' => $this->id('I'), 'to' => $this->id('V')]);
        $this->assertSame('Grades I–V', $scope->label());
        $this->assertCount(5, $scope->toStorage()['ids']);
    }

    public function test_reversed_range_is_normalized(): void
    {
        $scope = GradeScope::fromInput(['mode' => 'range', 'from' => $this->id('IX'), 'to' => $this->id('V')]);
        $this->assertSame('Grades V–IX', $scope->label());
    }

    public function test_specific_single_contiguous_and_scattered(): void
    {
        $this->assertSame('Grade III', GradeScope::fromInput(['mode' => 'specific', 'ids' => [$this->id('III')]])->label());
        $this->assertSame('Grades VI–VIII', GradeScope::fromInput(['mode' => 'specific', 'ids' => [$this->id('VIII'), $this->id('VI'), $this->id('VII')]])->label());
        $this->assertSame('Grades I, III, X', GradeScope::fromInput(['mode' => 'specific', 'ids' => [$this->id('X'), $this->id('I'), $this->id('III')]])->label());
    }

    public function test_round_trip_storage(): void
    {
        $scope = GradeScope::fromInput(['mode' => 'range', 'from' => $this->id('KG1'), 'to' => $this->id('II')]);
        $stored = $scope->toStorage();
        $this->assertSame($scope->label(), GradeScope::fromStorage($stored['mode'], $stored['ids'])->label());
    }

    public function test_unknown_grade_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GradeScope::fromInput(['mode' => 'specific', 'ids' => [999999]]);
    }

    public function test_invalid_mode_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        GradeScope::fromInput(['mode' => 'some']);
    }
}
