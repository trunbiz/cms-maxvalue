<?php

namespace App\Console\Commands;

use App\Services\EnglishPublicationService;
use Illuminate\Console\Command;

class PrepareEnglishPublication extends Command
{
    protected $signature = 'site:prepare-english';

    protected $description = 'Prepare English publication pages and archive unchanged seed examples without resetting the database';

    public function handle(EnglishPublicationService $service): int
    {
        $result = $service->prepare();
        $this->info($result['sample_posts_archived'].' untouched sample posts converted to English drafts.');
        $this->info($result['policy_pages'].' publication pages available. Complete Settings and review the drafts before publishing.');

        return self::SUCCESS;
    }
}
