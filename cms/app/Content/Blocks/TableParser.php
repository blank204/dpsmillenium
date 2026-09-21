<?php

namespace App\Content\Blocks;

use InvalidArgumentException;

final class TableParser
{
    /** @return array<int, array<int, string>> */
    public static function parse(string $pasted): array
    {
        $pasted = str_replace(["\r\n", "\r"], "\n", $pasted);
        $firstLine = strtok($pasted, "\n") ?: '';
        $delimiter = str_contains($firstLine, "\t") ? "\t" : ',';

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $pasted);
        rewind($handle);

        $rows = [];
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '')) !== false) {
            $rows[] = array_map(fn ($cell) => mb_substr(trim((string) $cell), 0, 500), $row);
        }
        fclose($handle);

        return self::fromRows($rows);
    }

    /** @return array<int, array<int, string>> */
    public static function fromRows(array $rows): array
    {
        $rows = array_values(array_map(
            fn ($row) => array_map(fn ($cell) => mb_substr(trim((string) $cell), 0, 500), array_values((array) $row)),
            $rows
        ));

        while ($rows !== [] && implode('', end($rows)) === '') {
            array_pop($rows);
        }
        if ($rows === []) {
            throw new InvalidArgumentException('The table is empty. Copy cells from Excel and paste them here.');
        }

        $maxRows = config('cms.limits.table_rows');
        $maxCols = config('cms.limits.table_cols');
        $width = max(array_map('count', $rows));

        if (count($rows) > $maxRows) {
            throw new InvalidArgumentException('The table has '.count($rows)." rows. The limit is {$maxRows}. Split it into two tables or upload a PDF.");
        }
        if ($width > $maxCols) {
            throw new InvalidArgumentException("The table has {$width} columns. The limit is {$maxCols}. Upload it as a PDF instead.");
        }

        return array_map(fn ($row) => array_pad($row, $width, ''), $rows);
    }
}
