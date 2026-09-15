# Data shapes

## Post
```json
{
  "id": 21,
  "section": "info | event | circular | newsletter",
  "title": "string <=150",
  "summary": "string <=300 | null",
  "slug": "string (fixed at first publish)",
  "url": "/{information|events|circulars|newsletters}/{id}-{slug}",
  "grades": { "mode": "none | all | range | specific", "ids": [4, 5, 6], "label": "Grades I-III | null" },
  "status": "draft | scheduled | live | hidden | deleted",
  "posted_at": "ISO-8601 +03:00 (first publish)",
  "updated_at": "ISO-8601 +03:00 | null (shown as Updated)",
  "pinned_until": "ISO-8601 | null",
  "remove_on": "ISO-8601 | null",
  "academic_year": "2026-27",
  "event": { "date_start": "YYYY-MM-DD", "date_end": "YYYY-MM-DD | null" },
  "cover": { "media_id": 60 },
  "newsletter": { "series": "Kindergarten Newsletter", "term": "Term I", "pdf_media_id": 71, "link": null },
  "blocks": ["see below"]
}
```

`status` above is the presentation status (`Post::adminStatus()`): it derives from the stored status (`draft | scheduled | published`) plus `remove_on` and soft-delete, so a stored `published` post can present as `live` or `hidden` depending on today's date, and a soft-deleted post presents as `deleted`.

## Blocks (`data-block` = `type`)
| type | fields |
|---|---|
| `text` | `html` (only p, br, strong/b, em/i, ul, ol, li, a[href]; pasted content is reduced to plain text) |
| `image` | `media_id`, `caption` (<=300 chars) |
| `gallery` | `items[]: {media_id, caption}` (1-60 items) |
| `pdf` | `files[]: {media_id, label}` (1-20 files, label <=150 chars) |
| `link` | `label` (<=100 chars), `url` (https/http only) |
| `youtube` | `url`, `video_id`, `mode: link \| player` |
| `video` | `media_id`, `caption` |
| `table` | `has_header`, `rows: string[][]` (cells may contain line breaks; <=500 rows, <=20 columns) |
| `html_page` | `html_page_id`, `mode: link \| embed` (embed is a sandboxed iframe; editors may keep an existing one but cannot add or change it) |

All blocks also carry `"version": 1`. A post can have at most 100 blocks.

## Media URLs
`/media/{token}/{rev}/{file}` where `file` is one of:
- Images: `400.webp`, `400.jpg`, `800.webp`, `800.jpg`, `1600.webp`, `1600.jpg`, `master.jpg`. Serve WebP with JPG fallback via `<picture>`.
- PDF: `file.pdf`; add `?download=1` to force download.
- Video: `file.mp4`.

Custom HTML pages are served separately, sandboxed, at `/pages/{token}` (never under `/media/`).

## Calendar entry
```json
{ "title": "Half yearly examinations", "type": "Exam", "start_date": "2026-10-04", "end_date": "2026-10-15", "grades": { "label": "All grades" }, "note": null }
```

## Homepage boxes
Newest *N* public posts per section, pinned first. *N* is a setting (configurable per section in `settings`, 1-20; defaults: Info 5, Circulars 4, Events 8, Newsletter 4). The public JSON API arrives in Phase 2 with these shapes.
