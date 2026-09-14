<?php

namespace Tests\Unit;

use App\Content\Blocks\TableParser;
use InvalidArgumentException;
use Tests\TestCase;

class TableParserTest extends TestCase
{
    public function test_parses_excel_tab_paste_and_pads_rows(): void
    {
        $rows = TableParser::parse("Class\tRoom\tRoll no.\r\nVII A\t101\t1-30\r\nVII B\t102\r\n\r\n");
        $this->assertSame([['Class', 'Room', 'Roll no.'], ['VII A', '101', '1-30'], ['VII B', '102', '']], $rows);
    }

    public function test_parses_csv_with_quoted_commas_and_newlines(): void
    {
        $rows = TableParser::parse("Subject,Notes\nMaths,\"Chapters 1, 2\"\nScience,\"Line one\nLine two\"");
        $this->assertSame([['Subject', 'Notes'], ['Maths', 'Chapters 1, 2'], ['Science', "Line one\nLine two"]], $rows);
    }

    public function test_rejects_too_many_columns(): void
    {
        config(['cms.limits.table_cols' => 3]);
        $this->expectException(InvalidArgumentException::class);
        TableParser::parse("a\tb\tc\td");
    }

    public function test_rejects_too_many_rows(): void
    {
        config(['cms.limits.table_rows' => 2]);
        $this->expectException(InvalidArgumentException::class);
        TableParser::parse("a\nb\nc");
    }

    public function test_from_rows_preserves_multiline_cells(): void
    {
        $this->assertSame([['a', "x\ny"], ['b', '']], TableParser::fromRows([['a', "x\ny"], ['b']]));
    }

    public function test_empty_paste_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TableParser::parse("  \n\n");
    }
}
