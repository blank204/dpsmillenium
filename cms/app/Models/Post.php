<?php

namespace App\Models;

use App\Content\GradeScope;
use App\Content\PostStatus;
use App\Content\Section;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $attributes = ['blocks' => '[]', 'grade_mode' => 'none', 'status' => 'draft'];

    protected function casts(): array
    {
        return [
            'section' => Section::class,
            'status' => PostStatus::class,
            'blocks' => 'array',
            'grade_ids' => 'array',
            'publish_at' => 'immutable_datetime',
            'first_published_at' => 'immutable_datetime',
            'updated_at_public' => 'immutable_datetime',
            'sort_at' => 'immutable_datetime',
            'remove_on' => 'immutable_datetime',
            'pinned_until' => 'immutable_datetime',
            'event_date_start' => 'immutable_date',
            'event_date_end' => 'immutable_date',
            'purged_at' => 'immutable_datetime',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PostVersion::class)->orderByDesc('version');
    }

    public function scopeOfSection(Builder $query, Section $section): Builder
    {
        return $query->where('section', $section->value);
    }

    public function scopePublicEligible(Builder $query, ?CarbonInterface $now = null): Builder
    {
        $now ??= CarbonImmutable::now();

        return $query
            ->whereNull('purged_at')
            ->where(function (Builder $q) use ($now) {
                $q->where('status', PostStatus::Published->value)
                    ->orWhere(fn (Builder $s) => $s->where('status', PostStatus::Scheduled->value)->where('publish_at', '<=', $now));
            })
            ->where(fn (Builder $q) => $q->whereNull('remove_on')->orWhere('remove_on', '>', $now));
    }

    public function scopeOrderedForDisplay(Builder $query, ?CarbonInterface $now = null): Builder
    {
        $now ??= CarbonImmutable::now();

        return $query
            ->orderByRaw('CASE WHEN pinned_until IS NOT NULL AND pinned_until > ? THEN 1 ELSE 0 END DESC', [$now->format('Y-m-d H:i:s')])
            ->orderByDesc('sort_at')
            ->orderByDesc('id');
    }

    public function isPinnedAt(CarbonInterface $now): bool
    {
        return $this->pinned_until !== null && $this->pinned_until->greaterThan($now);
    }

    public function adminStatus(?CarbonInterface $now = null): string
    {
        $now ??= CarbonImmutable::now();

        if ($this->trashed()) {
            return 'deleted';
        }
        if ($this->status === PostStatus::Draft) {
            return 'draft';
        }
        if ($this->status === PostStatus::Scheduled && $this->publish_at?->greaterThan($now)) {
            return 'scheduled';
        }
        if ($this->remove_on !== null && $this->remove_on->lessThanOrEqualTo($now)) {
            return 'hidden';
        }

        return 'live';
    }

    public function gradeScope(): GradeScope
    {
        return GradeScope::fromStorage($this->grade_mode, $this->grade_ids);
    }

    public function publicPath(): ?string
    {
        if ($this->slug === null || $this->status === PostStatus::Draft) {
            return null;
        }

        return '/'.$this->section->pathSegment().'/'.$this->id.'-'.$this->slug;
    }

    /** Everything needed to restore this post exactly. */
    public function snapshot(): array
    {
        return collect($this->getAttributes())
            ->except(['id', 'created_at', 'updated_at', 'search_text'])
            ->map(fn ($v, $k) => in_array($k, ['blocks', 'grade_ids'], true) && is_string($v) ? json_decode($v, true) : $v)
            ->all();
    }
}
