# DPS Millennium CMS — Design Spec

- **Date:** 2026-09-14
- **Status:** Approved in brainstorming, awaiting written-spec review
- **Site:** DPS Millennium, Doha, Qatar (official school website, CBSE, opening April 2027)
- **Repo:** `github.com/blank204/dpsmillenium`

## 1. Purpose

Let school staff with WhatsApp-level computer skills update the time-sensitive parts of the official website themselves, without IT help, while keeping a permanent, trustworthy record of every change.

## 2. Scope

### In scope (v1)
Four content "corners" plus a calendar:

| Corner | What it holds |
|---|---|
| Information corner | Announcements, competitions, registrations, messages, seating plans |
| Events corner | Reports of **past** events (photos, write-up) |
| Academic circulars | Syllabus, test and exam notices, usually with PDFs |
| Newsletter | Issues per series / academic year / term (cover + PDF or link) |
| Calendar | Holidays and exams, entered by hand |

### Out of scope (v1): do not build
- CMS for fees, construction/campus page, homepage countdown, emergency banner.
- Public forms or form submissions.
- Apply Now, online payment, parent/student login backend (blocked on school IT).
- Search inside PDF contents (build so it can be added later).
- Languages other than English (permanent).
- Legal pages (privacy/cookies/terms) until everything else is done.
- **Visual design of any screen.** All visual design, brand application and the tutorial animation are done by Claude Design. This project delivers backend, behaviour, structure and a handoff pack.

### Later phases (recorded, not designed here)
Media gallery (**flagged important**, needs its own frontend) · leadership profiles · Apply Now · parent/student login backend · payments · PDF text search · calendar sync with the school's chosen calendar tool · CBSE mandatory disclosure page · legal pages.

## 3. Constraints and context

- **Hosting unknown.** School IT has not provided technical details. The sister site (dpsmisdoha.com) runs WordPress on Apache, and `dpsmillennium.com` is a GoDaddy Website Builder placeholder that the team cannot access. Assume cheap shared cPanel hosting.
- **Budget:** as low as possible.
- **Stack decision:** PHP + MySQL, built so a modern frontend (e.g. Next.js/Astro) can replace the server-rendered pages later via the API.
- **Browsers:** old browsers and mobile phones must work. Internet Explorer is not supported.
- **Time zone:** all dates and times are Asia/Qatar (UTC+3, no DST).
- **Frontend prototypes** in this repo (`homepage/`, `about/`, `campus/`, `fee/`, `sponsors/`) may still change. Existing animations stay.

## 4. Chosen approach

**Laravel (PHP 8.2+) application with a MySQL/MariaDB database, three layers:**

1. **Admin panel.** Server-rendered CMS screens, functional and unstyled in Phase 1, restyled to Claude Design's designs later.
2. **Public rendering.** Server-rendered HTML for homepage box data, post pages, archive and calendar, so content is present without JavaScript and WhatsApp/social previews work.
3. **Read-only JSON API** (`/api/v1/...`) exposing the same data, as the migration path to a modern frontend.

Rejected alternatives:
- **Plain PHP without a framework:** security would be entirely hand-written.
- **WordPress custom post types:** cluttered admin, plugin security burden, hard to restyle, poor migration path.

Deployment must not require build tools on the server. A pre-built package (including `vendor/`) is uploaded, and database migrations run from a Super-admin-only web action or CLI if available.

## 5. Accounts and roles

| Account | How many | Who |
|---|---|---|
| **Editor** | One shared login | Teachers / IT staff doing day-to-day updates |
| **Super admin** | Separate login(s) | School IT; the student developer during development |

- **Super admin** can do everything an Editor can, plus the Super-admin-only actions below.
- **Name required:** every action that changes anything requires the person to **type their name**. The field **starts blank every time**, with no prefill or suggestions. This covers publish, schedule, edit, pin, delete, calendar changes and settings changes.
- **Login protection:**
  - Rate-limited: 5 failed attempts, then a 15-minute lockout per account + IP.
  - Session expires after 2 hours of inactivity.
  - "Forgot password" shows "Contact school IT" (no email system).
- **Password changes:**
  - Changing the shared Editor password invalidates all Editor sessions.
  - Super admin can also force-logout all sessions without changing the password.
- **Setup:** no default passwords. First-run setup forces both passwords to be set.
- **Future-proofing:** authentication is isolated behind one module so it can later be replaced with individual accounts or school SSO (Google/Microsoft) without touching content or audit data. School-email-only login remains an open option.

**Super admin only:** audit log · version restore · recycle-bin restore · settings · password/session management · custom HTML page upload.

## 6. Content model

### 6.1 Post (shared by all four corners)

| Field | Rules |
|---|---|
| `section` | `info` · `event` · `circular` · `newsletter` |
| `title` | Required, max 150 chars |
| `slug` | Generated at first publish, **never changes**. URL form `/{section-path}/{id}-{slug}` |
| `summary` | Optional, max 300 chars. Used for listings and social previews; auto-derived from first text block if empty |
| `blocks` | Ordered list of content blocks (§6.3) |
| `grades` | **Always optional.** One of: none · all grades · a range (from → to in the grade list) · specific grades. Stored as a set of grade IDs plus mode; displayed e.g. "Grades I–V" |
| `status` | `draft` · `scheduled` · `published` · `hidden` (past remove-on date) · `deleted` (recycle bin) |
| `publish_at` | Empty = publish now. Otherwise a future Qatar date/time |
| `first_published_at` | Set once, shown as "Posted" |
| `updated_at_public` | Set on edits after publish, shown as "Updated" |
| `sort_at` | Ordering key. Set on publish; **reset to now on every edit unless "Small fix" is ticked** |
| `remove_on` | Optional date/time. After it the post is hidden from the public everywhere (boxes, archive, search, post page) |
| `pinned_until` | Optional. If set it must be a future date/time; saving a pin without it is rejected. After it the post orders normally |
| `academic_year` | Derived from `first_published_at` using the academic-year start month setting (Newsletter: set explicitly) |
| `deleted_at` | Soft delete. Permanently purged after **7 days** |

**Section-specific fields:**

| Section | Extra fields |
|---|---|
| Event | `event_date` (required, the date the event happened; may be a range) · `cover_image` (required) |
| Circular | none |
| Newsletter | `series` (from settings, required) · `academic_year` (required) · `term` (required; free label such as "Term I") · `cover_image` (required) · exactly one of `pdf` or `external_link` (required) · blocks optional |
| Info | none |

### 6.2 Visibility rules

Evaluated at request time, all times in Qatar time:

1. **Public-eligible** = `status = published` AND (`remove_on` empty OR in the future).
2. **Homepage box** for a section = public-eligible posts of that section, ordered by: pinned posts whose `pinned_until` is in the future first, then `sort_at` descending. Take the first *N* (*N* is a per-section setting).
3. **Archive** = all public-eligible posts, including those currently in homepage boxes.
4. A scheduled post becomes `published` when `publish_at` passes. `first_published_at` and `sort_at` are set to `publish_at`.
5. An edited post that is live is republished immediately with `sort_at = now`, unless "Small fix" is ticked. In both cases `updated_at_public = now` and the change is logged.
6. `hidden`, `deleted` and purged posts are never shown publicly (a purged post keeps a minimal tombstone row: id + section). Their URL returns an HTTP 410 page saying "This notice is no longer available", linking to the archive.

### 6.3 Content blocks

| Block | Content | Rules |
|---|---|---|
| Text | Rich text | Allowed: paragraphs, bold, italic, bulleted/numbered lists, links. Everything else stripped server-side by an allow-list sanitizer |
| Image | One image, optional caption, crop/resize | See §9 |
| Gallery | Multiple images, optional captions | Max 60 images per block |
| PDF | One or more PDFs, each with a label | Max 20 MB each. Public: open in browser + download |
| Link button | Label + URL | http/https only |
| YouTube | URL + display mode | Mode `link` (plain link) or `player` (embedded, privacy-enhanced `youtube-nocookie.com`, loads on click) |
| Video | MP4 file | Max 50 MB, MP4 (H.264) only. Over-limit message tells editor to use a YouTube block |
| Table | Rows/columns of plain text | Paste from Excel/Google Sheets (tab-separated) or upload CSV; optional header row; max 500 rows × 20 columns |
| Custom HTML page | Reference to an uploaded sandboxed page | **Super admin only** to add or change; Editors editing a post that contains one may move or remove it but not add or change it (§10) |

Blocks are stored as validated JSON with a `type` and `version` per block, so new block types can be added without migrating existing posts.

**Search text:** on save, plain text is extracted from title, summary, text blocks, captions, PDF labels and table cells into a FULLTEXT-indexed column.

### 6.4 Calendar entry

| Field | Rules |
|---|---|
| `title` | Required, max 150 chars |
| `type` | From settings list (default: Holiday, Exam) |
| `start_date` | Required |
| `end_date` | Optional, ≥ start |
| `grades` | Optional (same model as posts) |
| `note` | Optional, plain text, max 500 chars |

Calendar entries are published immediately (no scheduling), are not part of the archive, and require a name for every change (logged).

### 6.5 Settings (Super admin)

| Setting | Default |
|---|---|
| Grade list (ordered, editable labels) | Pre-KG, KG1, KG2, I … XII |
| Homepage count per section | Info 5 · Circulars 4 · Events 8 · Newsletter 4 |
| Academic year start month | April |
| Calendar types | Holiday, Exam |
| Newsletter series | Empty; Super admin adds |

Deleting a grade, calendar type or series that is in use is blocked; it can be renamed or hidden instead.

## 7. Admin screens (functional requirements only)

### Editor
1. **Login.**
2. **Dashboard:** "New post" and "New calendar entry" actions; lists of *Recently published*, *Scheduled* (with time remaining), *Pinned* (with unpin dates), *Drafts*.
3. **New post, choose section:** Info / Event / Circular / Newsletter.
4. **Post editor:** section fields, add/reorder/remove blocks, autosave draft every 10 s and on blur. Drafts are shared, so any editor can continue one.
5. **Preview:** exact public rendering, phone and desktop widths.
6. **Publish dialog:** name (required, blank) · Now / Schedule (date + time) · Remove on (optional) · Pin + unpin date (optional) · "Small fix, don't move it to the top" (edits of live posts only).
7. **All posts:** filter by section and status (Draft / Scheduled / Live / Hidden), search, actions Edit · Duplicate · Delete (confirm + name).
8. **Calendar:** month grid, add/edit/delete entries.
9. **Recycle bin:** view only.

### Super admin (in addition)
10. **Audit log:** filter by date range, name, action, post. Entry detail shows a before/after diff. Read-only.
11. **Version history** per post: view any version, restore (restore = new logged change).
12. **Recycle bin restore.**
13. **Settings** (§6.5).
14. **Security:** change Editor password, change own password, list active sessions, log out all sessions.
15. **Custom HTML pages:** upload, list, delete (logged); each gets a sandboxed URL usable in a Custom HTML block.
16. **Backups:** list backups, download.
17. **System status:** PHP/DB versions, last backup, scheduler mode (cron or visitor-triggered), storage used.

**Error messages** must be plain language and say what to do next, e.g. "This video is 80 MB. The limit is 50 MB. Upload it to YouTube and add a YouTube block instead."

## 8. Public side

### 8.1 Homepage data
- The server injects the current box contents for Info, Events, Circulars and Newsletter, plus upcoming calendar entries, into the homepage HTML, so it works without JavaScript.
- Each box links to the archive pre-filtered to its section.
- Box item data: title, section, posted/updated dates, grades label, pinned flag, URL, and (Events/Newsletter) cover image.

### 8.2 Post pages
- URL paths: `/information/{id}-{slug}`, `/events/{id}-{slug}`, `/circulars/{id}-{slug}`, `/newsletters/{id}-{slug}`. A wrong slug with the correct ID redirects (301) to the canonical URL.
- Open Graph and Twitter meta: title, summary, cover image or school crest fallback.
- Print stylesheet hook: the structure must print cleanly (styling by Claude Design).
- Hidden or deleted: 410 page (§6.2).

### 8.3 Archive (`/archive`)
- **Filters:** section (All / Info / Events / Circulars / Newsletter) · academic year (default current) · month (default all) · search query.
- **Search:** MySQL FULLTEXT over the extracted search text, natural-language mode, with a LIKE fallback for queries under the minimum token length.
- **Results:** 20 per page, ordered by `sort_at` descending (relevance first when searching).
- **Shareable:** all filter state lives in the query string.
- **Empty states:** a message per filter combination, e.g. "No circulars in September 2026".

### 8.4 Calendar (`/calendar`)
- Month view and list view (list is the default on narrow screens), filter by type, ranges displayed as spans.

### 8.5 Performance and SEO
- **Caching:** full-page cache for public pages, invalidated on any publish, edit, delete, restore or calendar change, and when a scheduled post goes live or a remove-on / unpin time passes.
- **Sitemap:** `sitemap.xml` of public-eligible posts, `robots.txt`.
- **Admin pages:** `noindex`.

### 8.6 Public JSON API (read-only, v1)

| Endpoint | Returns |
|---|---|
| `GET /api/v1/posts?section=&limit=` | Homepage box data |
| `GET /api/v1/posts/{id}` | Full post including blocks |
| `GET /api/v1/archive?section=&year=&month=&q=&page=` | Archive page |
| `GET /api/v1/calendar?year=&month=` | Calendar entries |
| `GET /api/v1/meta` | Public settings: sections, grade list, academic years available, calendar types, newsletter series |

- Responses are JSON with ISO-8601 datetimes including the `+03:00` offset.
- Rate-limited, cached, and CORS limited to configured origins.

## 9. Media handling

All media processing is automatic and invisible to editors.

- **Accepted uploads:** JPEG, PNG, WebP, HEIC (converted if the server supports it; otherwise a friendly "please send as JPG" message). Max 15 MB per image upload.
- **On upload:**
  1. Apply EXIF orientation.
  2. **Strip all metadata including GPS.**
  3. Scale down to 2560 px longest edge.
  4. Generate variants: 400 / 800 / 1600 px.
  5. Produce WebP + JPEG fallback.
- **Crop and resize:** an editor tool stores the crop rectangle; variants are regenerated from the cleaned master.
- **Libraries:** GD or Imagick, whichever the host provides. Both code paths are tested.
- **Storage:** files are validated by content (MIME sniffing), not extension, stored under random names, outside any executable path, and served with correct `Content-Type` and `X-Content-Type-Options: nosniff`. PHP execution is disabled in upload directories.

## 10. Security

- **Passwords and requests:**
  - bcrypt or argon2id password hashing.
  - CSRF protection on all admin forms.
  - Eloquent/parameterised queries only.
  - Output escaping by default.
  - Server-side allow-list HTML sanitising for Text blocks.
- **Admin path:** a configurable, non-obvious base path (not `/admin`), plus `noindex` and rate-limited login.
- **Transport and headers:**
  - HTTPS enforced, HSTS.
  - `Content-Security-Policy` for public and admin pages.
  - `X-Frame-Options`/`frame-ancestors`, `Referrer-Policy`, `Permissions-Policy`.
- **Custom HTML pages:**
  - Stored separately.
  - Served from a dedicated path with `Content-Security-Policy: sandbox allow-scripts allow-popups` (no `allow-same-origin`), so they run in an opaque origin and cannot read site cookies or the admin.
  - Embedded via `<iframe sandbox>` or linked directly.
  - Upload is Super admin only.
- **Sessions:** secure, HttpOnly, SameSite=Lax cookies; session ID regenerated on login.
- **Errors:** production mode shows generic error pages. Details go to a private log.
- **Dependencies:** `composer audit` in CI and before every deploy.
- **Pre-launch:** OWASP Top 10 review checklist (whether the school requires a formal penetration test is still unknown, so build to that standard).

## 11. Audit log

- **Logged events:**
  - Login success and failure, logout, session expiry.
  - Post create / edit / publish / schedule / unschedule / pin / unpin / delete / restore / purge.
  - Version restore.
  - Calendar create / edit / delete.
  - Settings changes, password changes, force-logout.
  - Custom HTML upload / delete.
  - Backup download.
- **Each entry records:** id · timestamp (Qatar time) · typed name (or `—` for login events) · account type · action · target type + id · IP address · user agent · session ID · before JSON · after JSON · `prev_hash` · `hash`.
- **Append-only:**
  - The application has no update or delete code path for audit entries.
  - The database user for the app is granted INSERT/SELECT only on the audit table where the host allows per-table grants.
  - Entries are **never deleted**, not even by Super admin.
- **Tamper evidence:**
  - `hash = SHA-256(prev_hash + canonical entry JSON)`.
  - Super admin "Verify log integrity" action reports the first broken link, if any.
- **Versioning:** every post save also writes a full post version record (used for version history and restore).

## 12. Scheduling

- **Scheduled tasks:**
  - Publish due posts.
  - Hide posts past `remove_on`.
  - Expire pins.
  - Purge recycle bin (> 7 days).
  - Run the daily backup.
  - Invalidate caches.
- **Primary:** cron every minute (`schedule:run`) when the host supports it.
- **Fallback:** a lightweight check on public and admin requests runs due tasks, guarded by a lock and at most once per minute, so scheduled posts appear on time even without cron.
- **Public reads:** visibility rules (§6.2) are evaluated with the current time, so a post never shows late or lingers because a task has not run yet.

## 13. Backups and portability

- **Daily backups:** database dump + uploads archive, stored outside the web root, retained 30 days, downloadable by Super admin.
- **Portability:** migrations + restore from backup reproduce the site on any compatible host.
- **Hosting requirements sheet** (deliverable for IT): PHP ≥ 8.2 with extensions (pdo_mysql, mbstring, openssl, fileinfo, gd or imagick, zip), MySQL ≥ 8.0 or MariaDB ≥ 10.6, SSL certificate, cron availability, ≥ 10 GB storage, ability to point the web root to `public/`.

## 14. Environments

Local development (developer PC) → free/cheap staging for previews → production on school hosting once IT provides access. Configuration via `.env` only; no secrets committed.

## 15. Testing

Automated feature tests (PHPUnit/Pest) must cover:
- **Visibility:** box top-N, pins before others, pin expiry, remove-on hiding, archive includes box items.
- **Scheduling:** future posts invisible, then visible after `publish_at`, both with cron and visitor-triggered fallback.
- **Edit behaviour:** edit bumps `sort_at`, "Small fix" does not; Updated date shown in both cases.
- **Name required:** on every mutating action (post, calendar, settings, delete, restore).
- **Access control:** Editor cannot access Super admin routes or create Custom HTML blocks.
- **Audit log:** entry written for every logged event; no update/delete path; integrity verification detects a modified row.
- **Recycle bin:** purge after 7 days; Super admin restore.
- **Archive:** filters, academic-year derivation (April start and changed setting), search including short queries.
- **Uploads:** MIME validation rejects disguised files; EXIF/GPS removed; size limits; variants generated under both GD and Imagick.
- **Text blocks:** sanitizer strips disallowed HTML and scripts.
- **Post URLs:** canonical redirect; 410 for hidden and deleted posts.
- **Security:** login rate limiting; password change invalidates Editor sessions.
- **API:** response shapes match the handoff pack.

Plus: `composer audit`, the OWASP Top 10 checklist, and manual checks on an old Android browser and an older iOS Safari.

## 16. Deliverables

1. **Laravel application** in this repo (backend, admin, public rendering, API), with seeders for a realistic demo (sample posts in all sections, calendar entries, grades, series) and both logins so the developer can experience Editor and Super admin views.
2. **Hosting requirements sheet** for school IT.
3. **Claude Design handoff pack:**
   - screen inventory (public + admin)
   - every field with limits
   - every state (empty, draft, scheduled, pinned, hidden, deleted, upload failed, validation errors, very long title, PDF-only circular, table-heavy post, 60-image gallery…)
   - realistic sample data
   - grey-box structural wireframes (no styling)
   - API response shapes
4. **IT handover guide:** install/deploy, backups and restore, password changes, restoring a post, verifying audit log integrity.

## 17. Phases

| Phase | Contents |
|---|---|
| 1 | Laravel skeleton, DB schema + migrations, auth (two account types), content model, blocks, media pipeline, admin screens (unstyled), audit log + versions, recycle bin, scheduling, settings, tests. **Claude Design handoff pack.** |
| 2 | Public rendering (homepage data injection, post pages, archive + search, calendar), JSON API, caching, sitemap; integrate Claude Design's public and admin designs as they arrive. |
| 3 | Security hardening + OWASP review, backups, staging deploy, hosting requirements sheet, IT handover guide. |

## 18. Open items (not blocking Phase 1)

- School IT: hosting type and access, domain/DNS access, whether a formal pen test is required, whether to restrict login to school email later.
- Real grade list, academic year start month confirmation, newsletter series names.
- Which calendar system the school will use (possible future sync).
- Leadership names (for later leadership-profile CMS).
