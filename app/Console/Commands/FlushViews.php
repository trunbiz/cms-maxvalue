<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class FlushViews extends Command
{
    protected $signature = 'views:flush';

    protected $description = 'Flush Redis views to the database with crash recovery';

    public function handle(): int
    {
        return Cache::lock('views.flush.lock', 600)->block(3, function () {
            foreach (Redis::smembers('views:pending') as $member) {
                if (! preg_match('/^(post|series):(\d+)$/', $member, $m)) {
                    continue;
                }
                $counter = 'views:'.$member;
                $pending = 'views:batch:'.$member;
                $batch = Redis::eval("if redis.call('EXISTS',KEYS[2])==1 then return redis.call('GET',KEYS[2]) end; local n=tonumber(redis.call('GETSET',KEYS[1],'0') or '0'); if n==0 then return '' end; local b=ARGV[1]..':'..n; redis.call('SET',KEYS[2],b); return b", 2, $counter, $pending, (string) Str::uuid());
                if ($batch) {
                    [$uuid,$count] = explode(':', $batch);
                    DB::transaction(function () use ($uuid, $count, $m) {
                        if (DB::table('view_batches')->insertOrIgnore(['id' => $uuid, 'created_at' => now()])) {
                            DB::table($m[1] === 'post' ? 'posts' : 'series')->where('id', (int) $m[2])->increment('views', (int) $count);
                        }
                    });
                    Redis::eval("if redis.call('GET',KEYS[1])==ARGV[1] then return redis.call('DEL',KEYS[1]) end; return 0", 1, $pending, $batch);
                }
                Redis::eval("if tonumber(redis.call('GET',KEYS[1]) or '0')==0 and redis.call('EXISTS',KEYS[2])==0 then redis.call('DEL',KEYS[1]); redis.call('SREM',KEYS[3],ARGV[1]); end; return 1", 3, $counter, $pending, 'views:pending', $member);
            }
            $this->info('View counts synchronized.');

            return self::SUCCESS;
        });
    }
}
