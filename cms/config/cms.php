<?php

return [
    'admin_path' => trim(env('ADMIN_PATH', 'staff-cms'), '/'),
    'image_driver' => env('IMAGE_DRIVER', 'gd'),
    'limits' => [
        'image_bytes' => 15 * 1024 * 1024,
        // Pixel cap checked from the file header before decoding. GD needs roughly
        // 5 bytes per pixel, so 40 MP ≈ 200 MB: covers normal phone photos (12–16 MP,
        // 48–50 MP "full resolution" modes should be sent as standard) on a 256 MB host.
        'image_pixels' => 40_000_000,
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
