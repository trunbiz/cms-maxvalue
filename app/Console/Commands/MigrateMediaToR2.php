<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateMediaToR2 extends Command
{
    protected $signature = 'media:migrate-to-r2';

    protected $description = 'Sao chép media từ public sang R2, giữ nguyên file gốc';

    public function handle(): int
    {
        $source = Storage::disk('public');
        $target = Storage::disk('r2');
        $files = $source->allFiles();
        $bar = $this->output->createProgressBar(count($files));
        foreach ($files as $path) {
            if (! $target->exists($path)) {
                $stream = $source->readStream($path);
                try {
                    if (! $target->put($path, $stream)) {
                        throw new \RuntimeException('Không thể sao chép '.$path);
                    }
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            } $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $this->info('Đã sao chép media.');

        return self::SUCCESS;
    }
}
