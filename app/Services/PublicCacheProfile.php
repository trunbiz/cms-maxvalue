<?php

namespace App\Services;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;
use Symfony\Component\HttpFoundation\Response;

class PublicCacheProfile extends CacheAllSuccessfulGetRequests
{
    public function shouldCacheRequest(Request $request): bool
    {
        return $request->isMethod('GET') && ! is_file(public_path('hot'));
    }

    public function shouldCacheResponse(Response $response): bool
    {
        return $response->isSuccessful();
    }

    public function useCacheNameSuffix(Request $request): string
    {
        // Cached HTML must reference assets from the current Vite build.
        $manifest = public_path('build/manifest.json');

        return is_file($manifest) ? (hash_file('sha256', $manifest) ?: '') : '';
    }
}
