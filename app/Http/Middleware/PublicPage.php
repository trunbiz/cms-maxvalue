<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicPage
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }
        $response = $next($request);
        if ($response->isSuccessful()) {
            if (config('cloudflare.purge_enabled')) {
                Cache::lock('site.urls.lock', 10)->block(3, function () use ($request) {
                    $urls = Cache::get('site.cached_urls', []);
                    $url = url($request->getRequestUri());
                    if (! in_array($url, $urls, true)) {
                        $urls[] = $url;
                        Cache::forever('site.cached_urls', $urls);
                    }
                });
            }
            $response->headers->set('Cache-Control', 'public, max-age=0, s-maxage=600');
        }

        return $response;
    }
}
