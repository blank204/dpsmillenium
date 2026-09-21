<?php

/*
 * Nothing is served from the stateful `web` group at the site root.
 *
 * The public pages live in routes/public.php (stateless: no session, no cookies,
 * cacheable) and the staff CMS lives in routes/admin.php under config('cms.admin_path').
 * Both are registered from bootstrap/app.php.
 */
