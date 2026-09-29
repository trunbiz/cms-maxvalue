<?php

return [
    'enabled' => env('RESPONSE_CACHE_ENABLED', true),
    'cache_profile' => \App\Services\PublicCacheProfile::class,
    'cache_lifetime_in_seconds' => (int) env('RESPONSE_CACHE_LIFETIME', 600),
    'cache_store' => env('RESPONSE_CACHE_DRIVER', 'redis'), 'cache_tag' => 'reader.responses',
    'add_cache_time_header' => false, 'add_cache_age_header' => false, 'replacers' => [],
    'cache_bypass_header' => ['name' => null, 'value' => null],
    'hasher' => \Spatie\ResponseCache\Hasher\DefaultHasher::class,
    'serializer' => \Spatie\ResponseCache\Serializers\DefaultSerializer::class,
];
