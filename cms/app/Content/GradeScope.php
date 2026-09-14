<?php

namespace App\Content;

use App\Models\Grade;
use InvalidArgumentException;

final class GradeScope
{
    public const MODES = ['none', 'all', 'range', 'specific'];

    /** @param int[] $ids */
    private function __construct(public readonly string $mode, public readonly array $ids) {}

    public static function fromInput(array $input): self
    {
        $mode = $input['mode'] ?? 'none';
        if (! in_array($mode, self::MODES, true)) {
            throw new InvalidArgumentException('Invalid grade selection.');
        }

        if ($mode === 'none' || $mode === 'all') {
            return new self($mode, []);
        }

        $ordered = Grade::ordered()->get(['id', 'position']);
        $positions = $ordered->pluck('position', 'id');

        if ($mode === 'range') {
            $from = (int) ($input['from'] ?? 0);
            $to = (int) ($input['to'] ?? 0);
            if (! $positions->has($from) || ! $positions->has($to)) {
                throw new InvalidArgumentException('Choose both the first and last grade.');
            }
            [$lo, $hi] = [min($positions[$from], $positions[$to]), max($positions[$from], $positions[$to])];
            $ids = $ordered->filter(fn ($g) => $g->position >= $lo && $g->position <= $hi)->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

            return new self('range', $ids);
        }

        $ids = array_values(array_unique(array_map('intval', (array) ($input['ids'] ?? []))));
        if ($ids === [] || array_diff($ids, $positions->keys()->map(fn ($id) => (int) $id)->all()) !== []) {
            throw new InvalidArgumentException('Choose at least one valid grade.');
        }
        usort($ids, fn ($a, $b) => $positions[$a] <=> $positions[$b]);

        return new self('specific', $ids);
    }

    public static function fromStorage(?string $mode, ?array $ids): self
    {
        return new self(in_array($mode, self::MODES, true) ? $mode : 'none', array_map('intval', $ids ?? []));
    }

    /** @return array{mode: string, ids: int[]} */
    public function toStorage(): array
    {
        return ['mode' => $this->mode, 'ids' => $this->ids];
    }

    public function label(): ?string
    {
        if ($this->mode === 'none') {
            return null;
        }
        if ($this->mode === 'all') {
            return 'All grades';
        }

        $ordered = Grade::ordered()->get(['id', 'label']);
        $indexById = $ordered->pluck('id')->map(fn ($id) => (int) $id)->flip();
        $selected = $ordered->whereIn('id', $this->ids)->values();

        if ($selected->isEmpty()) {
            return null;
        }
        if ($selected->count() === 1) {
            return 'Grade '.$selected->first()->label;
        }

        $indexes = $selected->map(fn ($g) => $indexById[(int) $g->id])->all();
        $contiguous = max($indexes) - min($indexes) === count($indexes) - 1;

        return $contiguous
            ? 'Grades '.$selected->first()->label.'–'.$selected->last()->label
            : 'Grades '.$selected->pluck('label')->implode(', ');
    }
}
