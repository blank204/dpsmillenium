# Fields and limits

## Global limits
- Title max: 150
- Summary max: 300
- Calendar note max: 500
- Gallery max photos: 60
- Table max rows: 500
- Table max columns: 20
- Photo max MB: 15
- PDF max MB: 20
- Video max MB: 50
- Your name: required, 1-80 characters, always blank
- Custom HTML page file: 2 MB, `.html`/`.htm`
- Password (Editor or Super admin): at least 12 characters, with confirmation

## Post: common
| Field | Type | Required | Help text to show |
|---|---|---|---|
| Title | text | yes | none |
| Short summary | textarea | no | "Shown in lists and WhatsApp previews" |
| Grades | choice: Not grade-specific / All grades / A range (From, To) / Pick grades (checkboxes) | no | Displayed publicly as "Grades I-V", "Grade III", "Grades I, III, X", "All grades" |
| Content blocks | list | yes except Newsletter | see below |

## Post: by section
| Section | Extra fields |
|---|---|
| Events corner | Date the event happened (required), End date (optional, "if it ran over several days"), Cover photo (required) |
| Newsletter | Newsletter series (required, from Settings), Academic year (required, e.g. 2026-27), Term (required, free text e.g. "Term I"), Cover photo (required), **either** PDF **or** Link (exactly one) |
| Information corner, Academic circulars | none |

## Publish panel
| Field | Rules | Wording |
|---|---|---|
| Publish now / Schedule | schedule needs a future date+time, Qatar time | "Schedule for later (Qatar time)" |
| Remove from the website on | optional; must be after go-live | "Remove from the website on (optional)" |
| Pin | optional; requires Unpin on (future) | "Pin to the top of its box" / "Unpin on (required when pinned)" |
| Small fix | only when editing a live or hidden post | "Small fix, don't move it to the top". Note: "Parents will see “Updated” with today's date either way." |
| Buttons | Draft: Save draft, Preview, Publish. Scheduled: Preview, Update schedule. Live: Preview, Save changes | none |

## Blocks
| Block | Editor inputs | Limits |
|---|---|---|
| Text | plain HTML paragraph text (p, br, strong/b, em/i, ul, ol, li, a[href] only; other markup is stripped) | must not be empty |
| Photo | upload, caption, crop (x, y, w, h), max width | 15 MB; JPG/PNG/WebP (HEIC rejected with a conversion message) |
| Photo gallery | multi-upload, caption per photo, remove photo | 60 photos, at least 1 |
| PDF file(s) | multi-upload, label per PDF, remove | 1-20 PDFs per block, 20 MB each |
| Link button | label, web address | label up to 100 characters; https:// (or http://) only, must be a full valid URL |
| YouTube video | link, "Show as a link" / "Show the video player" | YouTube URLs only |
| Short video | MP4 upload, caption | 50 MB |
| Table | "First row is a heading", paste box, grid of cells, + Row, + Column | 500 rows x 20 columns |
| Custom HTML page | page ID, Link / Embed | Super admin only; editors can keep an existing one but not add or change it |

A post can have at most 100 blocks in total.

## Calendar entry
Title (150, required) - Type (Holiday/Exam, editable list) - Start date (required) - End date (optional) - Grades (optional) - Note (500) - Your name.

## Error messages (verbatim from the app; keep meaning if reworded)
- "Type your name before saving. This is recorded in the change log."
- "Username or password is incorrect."
- "Too many attempts. Try again in {N} minutes."
- "This video is {X} MB. The limit is 50 MB. Upload it to YouTube and add a YouTube block instead."
- "\"{filename}\" is an iPhone HEIC photo. Please send it as JPG (on iPhone: Settings → Camera → Formats → Most Compatible) and upload again."
- "\"{filename}\" is {X} MB. The limit is 15 MB per photo."
- "\"{filename}\" is {X} MB. The limit is 20 MB per PDF. Try compressing it."
- "The table has {N} rows. The limit is 500. Split it into two tables or upload a PDF."
- "The table has {N} columns. The limit is 20. Upload it as a PDF instead."
- "Pinned posts need an \"unpin on\" date."
- "The scheduled time has already passed. Choose a future time or publish now."
- "Only IT (Super admin) can add or change custom page blocks."
- "Add either a PDF or a link, not both."
- "The link must be a full web address starting with https://"
- "This is not an HTML page. Upload the .html file." (custom page upload)
