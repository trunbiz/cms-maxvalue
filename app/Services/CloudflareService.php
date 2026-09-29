<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CloudflareService
{
    public function purgeUrls(array $urls): void
    {
        if (! config('cloudflare.purge_enabled')) {
            return;
        }
        foreach (array_chunk(array_values(array_unique($urls)), 30) as $chunk) {
            $response = Http::withToken(config('cloudflare.api_token'))->timeout(20)->retry(3, 500)->post('https://api.cloudflare.com/client/v4/zones/'.config('cloudflare.zone_id').'/purge_cache', ['files' => $chunk])->throw();
            if (! $response->json('success')) {
                throw new \RuntimeException('Cloudflare rejected the cache purge request.');
            }
        }
    }
}
