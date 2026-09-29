<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TestR2 extends Command
{
    protected $signature = 'r2:test';

    protected $description = 'Test uploading, reading and deleting a file on R2';

    public function handle(): int
    {
        if (! config('filesystems.disks.r2.bucket') || ! config('filesystems.disks.r2.key')) {
            $this->error('R2 credentials have not been configured in .env.');

            return self::FAILURE;
        }
        $disk = Storage::disk('r2');
        $path = 'connection-tests/'.Str::uuid().'.txt';
        try {
            $disk->put($path, 'R2 OK');
            if ($disk->get($path) !== 'R2 OK') {
                throw new \RuntimeException('The test file content does not match.');
            }
        } finally {
            $disk->delete($path);
        }
        $this->info('R2 connection successful.');

        return self::SUCCESS;
    }
}
