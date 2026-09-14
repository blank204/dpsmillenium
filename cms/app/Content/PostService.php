<?php

namespace App\Content;

use App\Audit\AuditLogger;
use App\Content\Blocks\BlockNormalizer;
use App\Models\Post;
use App\Models\PostVersion;
use App\Support\Actor;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class PostService
{
    private const CONTENT_FIELDS = [
        'title', 'summary', 'blocks', 'grade_mode', 'grade_ids', 'event_date_start', 'event_date_end',
        'cover_media_id', 'newsletter_series_id', 'newsletter_term', 'newsletter_pdf_media_id', 'newsletter_link',
    ];

    public function __construct(
        private readonly BlockNormalizer $blocks,
        private readonly AuditLogger $audit,
    ) {}

    public function saveDraft(?Post $post, Section $section, array $data, Actor $actor, bool $isSuperAdmin): Post
    {
        if ($post !== null && $post->status !== PostStatus::Draft) {
            throw ValidationException::withMessages(['status' => 'This post is not a draft. Use Publish to save changes.']);
        }

        return DB::transaction(function () use ($post, $section, $data, $actor, $isSuperAdmin) {
            $post ??= new Post(['section' => $section, 'status' => PostStatus::Draft]);
            $before = $post->exists ? $post->snapshot() : null;
            if ($post->status === PostStatus::Draft) {
                $post->section = $section;
            }
            $this->fill($post, $data, $isSuperAdmin);
            $post->search_text = SearchText::fromPost($post);
            $post->save();

            return $this->recordChange($post, 'post.draft_saved', $actor, $before);
        });
    }

    public function publish(Post $post, array $data, PublishOptions $options, Actor $actor, bool $isSuperAdmin): Post
    {
        $original = $post->getAttributes();

        try {
            return $this->doPublish($post, $data, $options, $actor, $isSuperAdmin);
        } catch (\Throwable $e) {
            // A rejected publish must leave the in-memory model exactly as it was.
            $post->setRawAttributes($original, true);
            throw $e;
        }
    }

    private function doPublish(Post $post, array $data, PublishOptions $options, Actor $actor, bool $isSuperAdmin): Post
    {
        return DB::transaction(function () use ($post, $data, $options, $actor, $isSuperAdmin) {
            $now = CarbonImmutable::now();
            $before = $post->exists ? $post->snapshot() : null;
            $wasLive = $post->exists && in_array($post->adminStatus($now), ['live', 'hidden'], true);

            $this->fill($post, $data, $isSuperAdmin);
            $this->validateOptions($options, $now, $wasLive);

            if ($post->section === Section::Newsletter) {
                $post->academic_year = $data['newsletter_academic_year'] ?? $post->academic_year;
            }

            if ($wasLive) {
                $action = 'post.edited';
                $post->updated_at_public = $now;
                if (! $options->smallFix) {
                    $post->sort_at = $now;
                }
            } elseif ($options->publishAt !== null && $options->publishAt->greaterThan($now)) {
                $action = 'post.scheduled';
                $post->status = PostStatus::Scheduled;
                $post->publish_at = $options->publishAt;
                $post->first_published_at = $options->publishAt;
                $post->sort_at = $options->publishAt;
            } else {
                $action = 'post.published';
                $post->status = PostStatus::Published;
                $post->publish_at = $now;
                $post->first_published_at = $now;
                $post->sort_at = $now;
            }

            if ($post->section !== Section::Newsletter && $post->first_published_at !== null && ! $wasLive) {
                $post->academic_year = AcademicYear::forDate($post->first_published_at);
            }

            $post->remove_on = $options->removeOn;
            $post->pinned_until = $options->pinnedUntil;
            $post->slug ??= Str::limit(Str::slug($post->title), 160, '') ?: 'post';

            PostRules::assertPublishable($post);

            $post->search_text = SearchText::fromPost($post);
            $post->save();

            return $this->recordChange($post, $action, $actor, $before);
        });
    }

    public function unschedule(Post $post, Actor $actor): Post
    {
        if ($post->adminStatus() !== 'scheduled') {
            throw ValidationException::withMessages(['status' => 'Only scheduled posts can be unscheduled.']);
        }

        return DB::transaction(function () use ($post, $actor) {
            $before = $post->snapshot();
            $post->status = PostStatus::Draft;
            $post->publish_at = null;
            $post->first_published_at = null;
            $post->sort_at = null;
            $post->academic_year = $post->section === Section::Newsletter ? $post->academic_year : null;
            $post->save();

            return $this->recordChange($post, 'post.unscheduled', $actor, $before);
        });
    }

    public function setPin(Post $post, ?CarbonImmutable $until, Actor $actor): Post
    {
        if ($until !== null && $until->lessThanOrEqualTo(CarbonImmutable::now())) {
            throw ValidationException::withMessages(['pinned_until' => 'The unpin date must be in the future.']);
        }

        return DB::transaction(function () use ($post, $until, $actor) {
            $before = $post->snapshot();
            $post->pinned_until = $until;
            $post->save();

            return $this->recordChange($post, $until === null ? 'post.unpinned' : 'post.pinned', $actor, $before);
        });
    }

    public function delete(Post $post, Actor $actor): void
    {
        DB::transaction(function () use ($post, $actor) {
            $before = $post->snapshot();
            $post->delete();
            $this->audit->record('post.deleted', $actor, $post, $before, null);
        });
    }

    public function restore(Post $post, Actor $actor): Post
    {
        if (! $post->trashed() || $post->purged_at !== null) {
            throw ValidationException::withMessages(['post' => 'This post cannot be restored.']);
        }

        return DB::transaction(function () use ($post, $actor) {
            $post->restore();
            $this->audit->record('post.restored', $actor, $post, null, $post->snapshot());

            return $post->refresh();
        });
    }

    public function restoreVersion(Post $post, PostVersion $version, Actor $actor): Post
    {
        abort_unless((int) $version->post_id === (int) $post->id, 404);

        return DB::transaction(function () use ($post, $version, $actor) {
            $before = $post->snapshot();
            foreach (self::CONTENT_FIELDS as $field) {
                if (array_key_exists($field, $version->data)) {
                    $post->{$field} = $version->data[$field];
                }
            }
            if (in_array($post->adminStatus(), ['live', 'hidden'], true)) {
                $post->updated_at_public = CarbonImmutable::now();
            }
            $post->search_text = SearchText::fromPost($post);
            $post->save();

            return $this->recordChange($post, 'post.version_restored', $actor, $before);
        });
    }

    public function purgeExpired(?CarbonInterface $now = null): int
    {
        $cutoff = CarbonImmutable::instance($now ?? CarbonImmutable::now())->subDays((int) config('cms.recycle_days'));
        $count = 0;

        Post::onlyTrashed()->whereNull('purged_at')->where('deleted_at', '<=', $cutoff)->each(function (Post $post) use (&$count) {
            DB::transaction(function () use ($post) {
                $before = $post->snapshot();
                PostVersion::where('post_id', $post->id)->delete();
                $post->forceFill([
                    'title' => '[removed]',
                    'summary' => null,
                    'blocks' => [],
                    'grade_mode' => 'none',
                    'grade_ids' => null,
                    'search_text' => null,
                    'cover_media_id' => null,
                    'newsletter_pdf_media_id' => null,
                    'newsletter_link' => null,
                    'purged_at' => CarbonImmutable::now(),
                ])->saveQuietly();
                $this->audit->record('post.purged', Actor::system(), $post, $before, null);
            });
            $count++;
        });

        return $count;
    }

    private function fill(Post $post, array $data, bool $isSuperAdmin): void
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '' || mb_strlen($title) > config('cms.limits.title')) {
            throw ValidationException::withMessages(['title' => 'Add a title (up to '.config('cms.limits.title').' characters).']);
        }
        $summary = trim((string) ($data['summary'] ?? ''));
        if (mb_strlen($summary) > config('cms.limits.summary')) {
            throw ValidationException::withMessages(['summary' => 'The summary can be at most '.config('cms.limits.summary').' characters.']);
        }

        try {
            $grades = GradeScope::fromInput((array) ($data['grades'] ?? ['mode' => 'none']))->toStorage();
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['grades' => $e->getMessage()]);
        }

        $post->title = $title;
        $post->summary = $summary === '' ? null : $summary;
        $previousBlocks = $post->exists ? $post->getOriginal('blocks') : [];
        if (is_string($previousBlocks)) {
            $previousBlocks = json_decode($previousBlocks, true) ?? [];
        }
        $post->blocks = $this->blocks->normalize((array) ($data['blocks'] ?? []), $isSuperAdmin, $previousBlocks ?? []);
        $post->grade_mode = $grades['mode'];
        $post->grade_ids = $grades['ids'];

        $isEvent = $post->section === Section::Event;
        $isNewsletter = $post->section === Section::Newsletter;

        $post->event_date_start = $isEvent ? ($data['event_date_start'] ?? null) : null;
        $post->event_date_end = $isEvent ? ($data['event_date_end'] ?? null) : null;
        if ($isEvent && $post->event_date_start && $post->event_date_end && $post->event_date_end->lessThan($post->event_date_start)) {
            throw ValidationException::withMessages(['event_date_end' => 'The end date must be on or after the start date.']);
        }
        $post->cover_media_id = ($isEvent || $isNewsletter) ? ($data['cover_media_id'] ?? null) : null;
        $post->newsletter_series_id = $isNewsletter ? ($data['newsletter_series_id'] ?? null) : null;
        $post->newsletter_term = $isNewsletter ? (trim((string) ($data['newsletter_term'] ?? '')) ?: null) : null;
        $post->newsletter_pdf_media_id = $isNewsletter ? ($data['newsletter_pdf_media_id'] ?? null) : null;
        $post->newsletter_link = $isNewsletter ? (trim((string) ($data['newsletter_link'] ?? '')) ?: null) : null;
        if ($isNewsletter && isset($data['newsletter_academic_year'])) {
            $post->academic_year = $data['newsletter_academic_year'];
        }
        if ($post->newsletter_link !== null && ! in_array(strtolower((string) parse_url($post->newsletter_link, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw ValidationException::withMessages(['newsletter_link' => 'The link must start with https://']);
        }
    }

    private function validateOptions(PublishOptions $options, CarbonImmutable $now, bool $wasLive): void
    {
        $errors = [];
        if ($options->publishAt !== null && $wasLive && $options->publishAt->greaterThan($now)) {
            $errors['publish_at'] = 'This post is already live, so it cannot be scheduled. Delete it or edit it now.';
        }
        if ($options->publishAt !== null && ! $wasLive && $options->publishAt->lessThanOrEqualTo($now)) {
            $errors['publish_at'] = 'The scheduled time has already passed. Choose a future time or publish now.';
        }
        $goesLiveAt = $options->publishAt && $options->publishAt->greaterThan($now) ? $options->publishAt : $now;
        if ($options->removeOn !== null && $options->removeOn->lessThanOrEqualTo($goesLiveAt)) {
            $errors['remove_on'] = 'The "remove from site" date must be after the post goes live.';
        }
        if ($options->pinnedUntil !== null && $options->pinnedUntil->lessThanOrEqualTo($now)) {
            $errors['pinned_until'] = 'The unpin date must be in the future.';
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function recordChange(Post $post, string $action, Actor $actor, ?array $before): Post
    {
        $post->refresh();
        $after = $post->snapshot();
        PostVersion::create([
            'post_id' => $post->id,
            'version' => (int) PostVersion::where('post_id', $post->id)->lockForUpdate()->max('version') + 1,
            'data' => $after,
            'actor_name' => $actor->name,
            'created_at' => CarbonImmutable::now(),
        ]);
        $this->audit->record($action, $actor, $post, $before, $after);

        return $post;
    }
}
