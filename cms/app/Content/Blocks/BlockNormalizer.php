<?php

namespace App\Content\Blocks;

use App\Models\HtmlPage;
use App\Models\MediaAsset;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class BlockNormalizer
{
    public const MAX_BLOCKS = 100;

    public function __construct(private readonly TextSanitizer $sanitizer) {}

    public function normalize(array $input, bool $isSuperAdmin, array $previous = []): array
    {
        if (count($input) > self::MAX_BLOCKS) {
            throw ValidationException::withMessages(['blocks' => 'A post can have at most '.self::MAX_BLOCKS.' blocks.']);
        }

        $errors = [];
        $out = [];

        foreach (array_values($input) as $i => $block) {
            try {
                $out[] = $this->one(is_array($block) ? $block : [], $isSuperAdmin, $previous);
            } catch (InvalidArgumentException $e) {
                $errors["blocks.{$i}"] = 'Block '.($i + 1).': '.$e->getMessage();
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $out;
    }

    private function one(array $b, bool $isSuperAdmin, array $previous): array
    {
        $type = $b['type'] ?? null;

        return match ($type) {
            'text' => $this->text($b),
            'image' => ['type' => 'image', 'version' => 1, 'media_id' => $this->mediaId($b['media_id'] ?? null, 'image'), 'caption' => $this->caption($b['caption'] ?? null)],
            'gallery' => $this->gallery($b),
            'pdf' => $this->pdf($b),
            'link' => $this->link($b),
            'youtube' => $this->youtube($b),
            'video' => ['type' => 'video', 'version' => 1, 'media_id' => $this->mediaId($b['media_id'] ?? null, 'video'), 'caption' => $this->caption($b['caption'] ?? null)],
            'table' => $this->table($b),
            'html_page' => $this->htmlPage($b, $isSuperAdmin, $previous),
            default => throw new InvalidArgumentException('Unknown block type.'),
        };
    }

    private function text(array $b): array
    {
        $html = $this->sanitizer->clean((string) ($b['html'] ?? ''));
        if (trim(html_entity_decode(strip_tags($html))) === '') {
            throw new InvalidArgumentException('This text block is empty. Type something or remove the block.');
        }

        return ['type' => 'text', 'version' => 1, 'html' => $html];
    }

    private function mediaId(mixed $id, string $kind): int
    {
        $asset = is_numeric($id) ? MediaAsset::find((int) $id) : null;
        if ($asset === null || $asset->kind !== $kind) {
            $what = ['image' => 'photo', 'pdf' => 'PDF', 'video' => 'video'][$kind];
            throw new InvalidArgumentException("The {$what} is missing. Upload it again.");
        }

        return $asset->id;
    }

    private function caption(mixed $caption): ?string
    {
        $caption = trim((string) $caption);
        if (mb_strlen($caption) > 300) {
            throw new InvalidArgumentException('Captions can be at most 300 characters.');
        }

        return $caption === '' ? null : $caption;
    }

    private function gallery(array $b): array
    {
        $items = array_values((array) ($b['items'] ?? []));
        $max = config('cms.limits.gallery_images');
        if ($items === []) {
            throw new InvalidArgumentException('Add at least one photo to the gallery.');
        }
        if (count($items) > $max) {
            throw new InvalidArgumentException('A gallery can have at most '.$max.' photos. Split it into two galleries.');
        }

        return ['type' => 'gallery', 'version' => 1, 'items' => array_map(fn ($item) => [
            'media_id' => $this->mediaId($item['media_id'] ?? null, 'image'),
            'caption' => $this->caption($item['caption'] ?? null),
        ], $items)];
    }

    private function pdf(array $b): array
    {
        $files = array_values((array) ($b['files'] ?? []));
        if ($files === [] || count($files) > 20) {
            throw new InvalidArgumentException('Add between 1 and 20 PDFs.');
        }

        return ['type' => 'pdf', 'version' => 1, 'files' => array_map(function ($file) {
            $id = $this->mediaId($file['media_id'] ?? null, 'pdf');
            $label = trim((string) ($file['label'] ?? ''));
            if ($label === '') {
                $label = pathinfo(MediaAsset::find($id)->original_name, PATHINFO_FILENAME);
            }

            return ['media_id' => $id, 'label' => mb_substr($label, 0, 150)];
        }, $files)];
    }

    private function link(array $b): array
    {
        $label = trim((string) ($b['label'] ?? ''));
        $url = trim((string) ($b['url'] ?? ''));
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if ($label === '' || mb_strlen($label) > 100) {
            throw new InvalidArgumentException('Give the button a label (up to 100 characters).');
        }
        if (! in_array($scheme, ['http', 'https'], true) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('The link must be a full web address starting with https://');
        }

        return ['type' => 'link', 'version' => 1, 'label' => $label, 'url' => $url];
    }

    private function youtube(array $b): array
    {
        $url = trim((string) ($b['url'] ?? ''));
        $id = YouTube::videoId($url);
        if ($id === null) {
            throw new InvalidArgumentException('That is not a YouTube video link. Copy the link from the YouTube Share button.');
        }
        $mode = ($b['mode'] ?? 'link') === 'player' ? 'player' : 'link';

        return ['type' => 'youtube', 'version' => 1, 'url' => $url, 'video_id' => $id, 'mode' => $mode];
    }

    private function table(array $b): array
    {
        $rows = isset($b['paste']) && is_string($b['paste'])
            ? TableParser::parse($b['paste'])
            : TableParser::fromRows((array) ($b['rows'] ?? []));

        return ['type' => 'table', 'version' => 1, 'has_header' => (bool) ($b['has_header'] ?? false), 'rows' => $rows];
    }

    private function htmlPage(array $b, bool $isSuperAdmin, array $previous): array
    {
        $normalized = [
            'type' => 'html_page',
            'version' => 1,
            'html_page_id' => (int) ($b['html_page_id'] ?? 0),
            'mode' => ($b['mode'] ?? 'link') === 'embed' ? 'embed' : 'link',
        ];

        if (! HtmlPage::whereKey($normalized['html_page_id'])->exists()) {
            throw new InvalidArgumentException('That custom page no longer exists.');
        }

        if (! $isSuperAdmin) {
            $unchanged = collect($previous)->contains(fn ($p) => ($p['type'] ?? null) === 'html_page'
                && (int) ($p['html_page_id'] ?? 0) === $normalized['html_page_id']
                && ($p['mode'] ?? null) === $normalized['mode']);
            if (! $unchanged) {
                throw new InvalidArgumentException('Only IT (Super admin) can add or change custom page blocks.');
            }
        }

        return $normalized;
    }
}
