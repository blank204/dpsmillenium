<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = ['label', 'position', 'hidden'];

    protected function casts(): array
    {
        return ['hidden' => 'boolean', 'position' => 'integer'];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('hidden', false);
    }
}
