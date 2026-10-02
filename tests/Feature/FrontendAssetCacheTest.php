<?php

namespace Tests\Feature;

use App\Services\PublicCacheProfile;
use Illuminate\Http\Request;
use Tests\TestCase;

class FrontendAssetCacheTest extends TestCase
{
    public function test_new_build_changes_response_cache_key_and_dev_server_bypasses_cache(): void
    {
        $original = public_path();
        $temporary = sys_get_temp_dir().'/cms-assets-'.bin2hex(random_bytes(8));
        mkdir($temporary.'/build', 0777, true);
        $this->app->usePublicPath($temporary);
        try {
            $profile = new PublicCacheProfile;
            $request = Request::create('/stories/example/chapter/author');
            file_put_contents($temporary.'/build/manifest.json', '{"file":"app-old.css"}');
            $old = $profile->useCacheNameSuffix($request);
            $this->assertSame($old, $profile->useCacheNameSuffix($request));
            file_put_contents($temporary.'/build/manifest.json', '{"file":"app-new.css"}');
            $this->assertNotSame($old, $profile->useCacheNameSuffix($request));
            $this->assertTrue($profile->shouldCacheRequest($request));
            file_put_contents($temporary.'/hot', 'http://localhost:5173');
            $this->assertFalse($profile->shouldCacheRequest($request));
        } finally {
            $this->app->usePublicPath($original);
            if (is_file($temporary.'/hot')) unlink($temporary.'/hot');
            unlink($temporary.'/build/manifest.json');
            rmdir($temporary.'/build');
            rmdir($temporary);
        }
    }
}
