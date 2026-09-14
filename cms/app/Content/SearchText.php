<?php

namespace App\Content;

use App\Models\Post;

final class SearchText
{
    public static function fromPost(Post $post): string
    {
        $parts = [$post->title, $post->summary, $post->newsletter_term];

        foreach ($post->blocks ?? [] as $block) {
            $parts = array_merge($parts, match ($block['type'] ?? null) {
                'text' => [html_entity_decode(strip_tags(str_replace(['</p>', '<br>', '</li>'], ' ', $block['html'] ?? '')))],
                'image', 'video' => [$block['caption'] ?? null],
                'gallery' => array_column($block['items'] ?? [], 'caption'),
                'pdf' => array_column($block['files'] ?? [], 'label'),
                'link' => [$block['label'] ?? null],
                'table' => array_merge(...array_map(fn ($r) => array_values($r), $block['rows'] ?? [[]])),
                default => [],
            });
        }

        return trim(preg_replace('/\s+/u', ' ', implode(' ', array_filter($parts, fn ($p) => is_string($p) && $p !== ''))));
    }
}
