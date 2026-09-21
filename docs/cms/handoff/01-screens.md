# Screens

Base path: `/staff-cms` (configurable via `ADMIN_PATH`). E = Editor, S = Super admin.

| `data-screen` | URL | Who | Purpose | Must contain |
|---|---|---|---|---|
| `login` | `/login` | E, S | Sign in | Username, password, error "Username or password is incorrect.", lockout message "Too many attempts. Try again in N minutes.", "Forgot your password? Contact school IT." |
| `dashboard` | `/` | E, S | Start here | Primary actions **New post** and **Calendar entry**; lists: Scheduled (title, section, go-live time + relative), Pinned (unpin date, "Unpin now" with name), Drafts, Recently published |
| `choose-section` | `/posts/new` | E, S | Pick what to post | Four large choices: Information corner, Events corner, Academic circulars, Newsletter |
| `post-editor` | `/posts/new/{section}`, `/posts/{id}` | E, S | Write and publish | Status line (Draft/Scheduled/Live/Hidden, Posted date, Updated date, public link, S: Version history); Basics (title, summary); Grades (none/all/range/pick); Event details (event only); Cover photo (event, newsletter); Newsletter issue fields; Content blocks (add, reorder, remove, per-block editors); autosave status line; Publish panel; Unschedule (scheduled only) |
| `post-preview` | opens in new tab | E, S | See it as parents will | Phone/Desktop toggle; rendered post; problems list if content invalid |
| `posts-index` | `/posts` | E, S | Find any post | Filters (section, status, title search); table (title + pinned marker, section, status, date, actions Edit / Duplicate / Delete with name) |
| `calendar` | `/calendar` | E, S | Holidays and exams | Month navigation; month grid with entries by type; add/edit form (title, type, start, end, grades, note, name); delete with name |
| `recycle-bin` | `/recycle-bin` | E (view), S (restore) | Undo deletes | Deleted posts with deleted date and "removed permanently on" date; S: Restore with name |
| `audit-index` | `/change-log` | S | Who changed what | "Check the log has not been altered" action; filters (name, action, dates, post id); table (#, when, name typed, login, action, item, IP, details) |
| `audit-show` | `/change-log/{id}` | S | One change in detail | When (Qatar time), name typed, login, IP, device, session, item, integrity hash; Before/After table of changed fields |
| `post-versions` | `/posts/{id}/versions` | S | Restore older content | Versions (number, time, by, title), expandable rendered content, Restore with name; link back to the post's change log entries |
| `settings` | `/settings` | S | Configure lists | Homepage count per section (1-20); academic year start month; editable lists for Grades (name, order, hidden), Calendar types (name, hidden), Newsletters (name, hidden), each with "Add new" row |
| `security` | `/security` | S | Passwords & sessions | Change shared Editor password (min 12 chars, explains everyone is logged out); change own password (current + new, min 12 chars); log out everyone; active sessions table (login, IP, device, last active) |
| `custom-pages` | `/custom-pages` | S | Isolated HTML pages | Explanation; upload (title, .html/.htm file up to 2 MB, name); table (ID, title opens page, size, date, remove with name) |
| `system` | `/system` | S | Health check | PHP version, database version, image library, PHP extensions (yes/NO per extension), scheduled tasks status (last run + source, or "no cron detected" note), storage used, backups status |
| `tutorial` | `/how-to-post` | E, S | Step-by-step help | Six numbered steps for posting a circular, plus "Things worth knowing" and "If something goes wrong" panels; links to New post and the Dashboard |

Global: navigation (Dashboard, New post, All posts, Calendar, Recycle bin; S adds Change log, Settings, Security, Custom pages, System; Help adds How to post and a link to the public site), "Signed in as Editor/Super admin", Log out, success message banner (`role="status"`), error summary (`data-region="form-errors"` in `01`/`02`/other screens via `admin.partials.errors`).

## Note on `login`

The `login` screen exists and works (`/staff-cms/login`), but did not carry a `data-screen` hook before this handoff pack. It has been added (`resources/views/admin/auth/login.blade.php`, wrapping the login `<form>` in `<div data-screen="login">`) so every screen listed here has a matching hook in code, per the project's own consistency rule.
