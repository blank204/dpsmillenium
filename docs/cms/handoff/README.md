# DPS Millennium CMS: Handoff pack for Claude Design

This folder describes **what exists and how it behaves**. It deliberately contains **no visual design**. Colours, typography, layout, motion and brand application are yours to decide, following DPS Millennium brand rules and the existing site prototypes (`homepage/`, `about/`, `campus/`, `fee/`, `sponsors/`).

## Who uses it
- **Editors:** teachers with WhatsApp-level computer skills, sharing one login. Design for zero training, big obvious actions and plain language.
- **Super admin:** school IT. Has everything editors have, plus the change log, version restore, settings and security.
- **Parents and students:** public visitors, mostly on phones, some on old browsers. Public pages are Phase 2, but the post content blocks below are what they will see.

## What to design
1. **Admin screens** (`01-screens.md`). Every screen already works unstyled at `/staff-cms` (run the demo seeder, `php artisan migrate:fresh --seed --class=Database\\Seeders\\DemoSeeder`, then log in as `editor` / `demo-editor-pass` or `superadmin` / `demo-super-pass`).
2. **Every state** in `03-states.md`. Empty, error and edge states are required, not optional.
3. **Content block rendering** for public post pages (the `data-block` types in `06-data-shapes.md`).
4. **A tutorial animation** for editors: "posting a circular in 60 seconds".

## Rules that must survive the redesign
- **Name field:** the "Your name" field appears on **every** save, publish, delete, restore and settings change. It is **always empty** when shown. Never pre-fill it, never suggest names.
- **Publish panel:** the publish controls (now/schedule, remove-on date, pin + unpin date, small fix) may become a dialog, but all fields must remain.
- **Plain language:** say "Remove from the website on", not "expiry".
- **Recycle bin:** editors see it but cannot restore. Restoring is an IT action.
- **Custom HTML pages:** IT only. Editors see an existing block but cannot change it.
- **Hooks:** keep the `data-screen`, `data-region`, `data-state`, `data-block` attributes; tests and scripts rely on them.
- **No build step:** the admin must work without a build step, so CSS and plain JS files only. Grey-box structure is in `05-wireframes.html`.

## Files
| File | Contents |
|---|---|
| `01-screens.md` | Screen inventory with purpose, content and actions |
| `02-fields-and-limits.md` | Every input with its limit and wording |
| `03-states.md` | Every state to design |
| `04-sample-data.json` | Realistic content to design with |
| `05-wireframes.html` | Grey-box structure (open in a browser) |
| `06-data-shapes.md` | Post, block and calendar data shapes |
