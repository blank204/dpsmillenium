<?php

return [
    'admin_path' => trim(env('ADMIN_PATH', 'staff-cms'), '/'),
    'image_driver' => env('IMAGE_DRIVER', 'gd'),
    'limits' => [
        'image_bytes' => 15 * 1024 * 1024,
        'pdf_bytes' => 20 * 1024 * 1024,
        'video_bytes' => 50 * 1024 * 1024,
        'gallery_images' => 60,
        'table_rows' => 500,
        'table_cols' => 20,
        'title' => 150,
        'summary' => 300,
        'calendar_note' => 500,
        'actor_name' => 80,
    ],
    'recycle_days' => 7,
    'login' => ['max_attempts' => 5, 'lockout_seconds' => 900],
];
