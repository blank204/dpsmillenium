<?php

namespace App\Audit;

final class Diff
{
    /** @return array<string, array{before: mixed, after: mixed}> */
    public static function between(?array $before, ?array $after): array
    {
        $before ??= [];
        $after ??= [];
        $out = [];
        foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
            $b = $before[$key] ?? null;
            $a = $after[$key] ?? null;
            if ($b !== $a) {
                $out[$key] = ['before' => $b, 'after' => $a];
            }
        }

        return $out;
    }
}
