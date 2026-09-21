<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class NewsletterSeries extends Model
{
    protected $table = 'newsletter_series';

    protected $fillable = ['name', 'hidden'];

    protected function casts(): array
    {
        return ['hidden' => 'boolean'];
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('hidden', false);
    }
}
