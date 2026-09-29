<?php

namespace App\Jobs;

use App\Services\CloudflareService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PurgeCloudflare implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public array $urls) {}

    public function backoff(): array
    {
        return [10, 60, 180];
    }

    public function handle(CloudflareService $service): void
    {
        $service->purgeUrls($this->urls);
    }
}
