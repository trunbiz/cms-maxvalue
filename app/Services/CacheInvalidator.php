<?php

namespace App\Services;

use App\Jobs\PurgeCloudflare;
use Illuminate\Support\Facades\Cache;
use Spatie\ResponseCache\Facades\ResponseCache;

class CacheInvalidator
{
    public function invalidate(): void
    {
        // Track every rendered URL, including pagination/search and old slugs.
        $urls = Cache::get('site.cached_urls', []);
        foreach (['site.settings', 'site.categories', 'site.menus'] as $key) {
            Cache::forget($key);
        }
        ResponseCache::clear();
        if (config('cloudflare.purge_enabled')) {
            PurgeCloudflare::dispatch(array_unique(array_merge([url('/'), url('/sitemap.xml'), url('/ads.txt')], $urls)))->afterCommit();
        }
    }
}
