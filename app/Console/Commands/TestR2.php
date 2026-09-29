<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TestR2 extends Command
{
    protected $signature = 'r2:test';

    protected $description = 'Kiểm tra ghi, đọc và xóa file thử trên R2';

    public function handle(): int
    {
        if (! config('filesystems.disks.r2.bucket') || ! config('filesystems.disks.r2.key')) {
            $this->error('Chưa cấu hình thông tin R2 trong .env.');

            return self::FAILURE;
        }
        $disk = Storage::disk('r2');
        $path = 'connection-tests/'.Str::uuid().'.txt';
        try {
            $disk->put($path, 'R2 OK');
            if ($disk->get($path) !== 'R2 OK') {
                throw new \RuntimeException('Nội dung kiểm tra không khớp.');
            }
        } finally {
            $disk->delete($path);
        }
        $this->info('Kết nối R2 thành công.');

        return self::SUCCESS;
    }
}
