<?php

namespace App\Services;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;
use Symfony\Component\HttpFoundation\Response;

class PublicCacheProfile extends CacheAllSuccessfulGetRequests
{
    public function shouldCacheRequest(Request $request): bool
    {
        return $request->isMethod('GET');
    }

    public function shouldCacheResponse(Response $response): bool
    {
        return $response->isSuccessful();
    }

    public function useCacheNameSuffix(Request $request): string
    {
        return '';
    }
}
