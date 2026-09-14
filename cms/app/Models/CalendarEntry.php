<?php

namespace App\Models;

use App\Content\GradeScope;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarEntry extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['start_date' => 'immutable_date', 'end_date' => 'immutable_date', 'grade_ids' => 'array'];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CalendarType::class, 'calendar_type_id');
    }

    public function scopeOverlapping(Builder $query, CarbonInterface $from, CarbonInterface $toExclusive): Builder
    {
        return $query
            ->where('start_date', '<', $toExclusive->format('Y-m-d'))
            ->whereRaw('COALESCE(end_date, start_date) >= ?', [$from->format('Y-m-d')]);
    }

    public function gradeScope(): GradeScope
    {
        return GradeScope::fromStorage($this->grade_mode, $this->grade_ids);
    }

    public function snapshot(): array
    {
        return [
            'title' => $this->title,
            'calendar_type_id' => (int) $this->calendar_type_id,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'grade_mode' => $this->grade_mode,
            'grade_ids' => $this->grade_ids,
            'note' => $this->note,
        ];
    }
}
