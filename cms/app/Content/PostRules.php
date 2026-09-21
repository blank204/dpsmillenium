<?php

namespace App\Content;

use App\Models\MediaAsset;
use App\Models\NewsletterSeries;
use App\Models\Post;
use Illuminate\Validation\ValidationException;

final class PostRules
{
    public static function assertPublishable(Post $post): void
    {
        $errors = [];

        if (trim((string) $post->title) === '') {
            $errors['title'] = 'Add a title.';
        }

        if ($post->section === Section::Event || $post->section === Section::Newsletter) {
            if (! MediaAsset::whereKey($post->cover_media_id)->where('kind', 'image')->exists()) {
                $errors['cover_media_id'] = 'Add a cover photo.';
            }
        }

        if ($post->section === Section::Event && $post->event_date_start === null) {
            $errors['event_date_start'] = 'Add the date the event happened.';
        }

        if ($post->section === Section::Newsletter) {
            if (! NewsletterSeries::whereKey($post->newsletter_series_id)->exists()) {
                $errors['newsletter_series_id'] = 'Choose which newsletter this is.';
            }
            if (! preg_match('/^\d{4}-\d{2}$/', (string) $post->academic_year)) {
                $errors['newsletter_academic_year'] = 'Choose the academic year.';
            }
            if (trim((string) $post->newsletter_term) === '') {
                $errors['newsletter_term'] = 'Add the term, for example "Term I".';
            }
            $hasPdf = MediaAsset::whereKey($post->newsletter_pdf_media_id)->where('kind', 'pdf')->exists();
            $hasLink = trim((string) $post->newsletter_link) !== '';
            if ($hasPdf === $hasLink) {
                $errors['newsletter_pdf_media_id'] = 'Add either a PDF or a link, not both.';
            }
        } elseif ($post->blocks === [] || $post->blocks === null) {
            $errors['blocks'] = 'Add at least one block of content.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
