# States to design

## Post status (`data-state` on post editor, list rows)
- `new`: not saved yet
- `draft`: saved, not public
- `scheduled`: will go live at a set time (show countdown / relative time)
- `live`: public
- `hidden`: past its "remove from website" date (public cannot see it, but the post is not deleted)
- `deleted`: in recycle bin (show "removed permanently on ...")
- pinned (combined with `live`): show unpin date

## Per screen
| Screen | States |
|---|---|
| Dashboard | each list empty ("Nothing scheduled.", "Nothing pinned.", "No drafts.", "Nothing published yet. Start with “New post”."); many items (20+ drafts) |
| Post editor | no blocks yet; 30+ blocks; very long title (150 chars, no spaces); validation errors at top + next to fields; per-block error ("Block 3: The link must be a full web address..."); upload in progress; upload failed; restore-unsaved-changes bar (autosave, keyed per post/new-section); live or hidden post (Save changes + Small fix checkbox); scheduled post (Update schedule + separate Unschedule form); newsletter with no series configured ("No newsletters set up yet. Ask IT to add one in Settings.") |
| Blocks | text empty (rejected: "This text block is empty. Type something or remove the block."); photo not uploaded; photo uploaded with crop panel open; gallery with 1 and 60 photos; PDF with long label; table 1x1 and 500x20 (horizontal scroll); multi-line table cells; YouTube link mode vs player mode; custom page block as seen by Editor (locked, existing block only) vs Super admin (can add/change) |
| Preview | valid; invalid content (problems list); phone width vs desktop width toggle |
| All posts | no results for filters ("No posts match these filters."); paginated (25 per page) |
| Calendar | month with no entries; day with 3+ entries; range crossing months; edit mode (loaded via `?edit={id}`); validation error (end before start) |
| Recycle bin | empty ("The recycle bin is empty."); editor view (no restore action); super admin view (Restore button, requires name) |
| Change log | empty filters result ("No entries match these filters."); integrity OK banner ("Change log intact: no entries have been altered."); integrity ALTERED warning banner ("WARNING: change log ALTERED at or before entry #N. Tell the school management and keep a backup copy.") |
| Change detail | no field changes ("No field changes recorded for this action." - e.g. login events); large/JSON values (blocks) shown as formatted `<pre>` |
| Settings | new row ("Add new..." placeholder, not required until filled); duplicate name error |
| Security | password too short (min 12); confirmation mismatch; wrong current password ("Your current password is incorrect."); no active sessions |
| Custom pages | none uploaded ("No custom pages yet."); wrong file type error ("This is not an HTML page. Upload the .html file.") |
| System | no cron detected ("no cron detected; posts still go live on time when anyone visits"); missing PHP extension shown as "NO"; backups "Not set up yet (Phase 3)" |
| Login | wrong password; locked out (N minutes, from "Too many attempts. Try again in N minutes."); session expired -> back at login |
