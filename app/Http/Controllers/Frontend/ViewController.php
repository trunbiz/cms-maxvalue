<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\ViewRequest;
use App\Models\Post;
use App\Models\Series;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class ViewController extends Controller
{
    public function __invoke(ViewRequest $request)
    {
        $type = $request->validated('type');
        $id = $request->integer('id');
        $class = $type === 'post' ? Post::class : Series::class;
        abort_unless($class::published()->whereKey($id)->exists(), 404);
        $fingerprint = hash('sha256', $request->ip().'|'.$request->userAgent());
        if (Cache::add('view.dedupe.'.$type.'.'.$id.'.'.$fingerprint, 1, 60)) {
            Redis::eval("redis.call('INCR',KEYS[1]); redis.call('SADD',KEYS[2],ARGV[1]); return 1", 2, 'views:'.$type.':'.$id, 'views:pending', $type.':'.$id);
        }

        return response()->noContent();
    }
}
