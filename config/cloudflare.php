<?php

return [
    'media_disk' => env('MEDIA_DISK', 'public'), 'r2_url' => env('R2_URL'),
    'zone_id' => env('CLOUDFLARE_ZONE_ID'), 'api_token' => env('CLOUDFLARE_API_TOKEN'),
    'purge_enabled' => (bool) env('CLOUDFLARE_PURGE_ENABLED', false),
];
