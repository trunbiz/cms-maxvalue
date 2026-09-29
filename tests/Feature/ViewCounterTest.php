<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Tests\TestCase;

class ViewCounterTest extends TestCase
{
    use RefreshDatabase;

    private array $keys = [];

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.redis.options.prefix' => 'cms_test_'.Str::uuid().'_']);
        Redis::purge('default');
        try {
            Redis::ping();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Cần Redis local để chạy kiểm thử tích hợp lượt xem.');
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->keys as $key) {
            Redis::del($key);
        }
        Redis::purge('default');
        parent::tearDown();
    }

    public function test_views_increment_deduplicate_and_flush_exactly_once(): void
    {
        $post = Post::factory()->create();
        $counter = 'views:post:'.$post->id;
        $pending = 'views:batch:post:'.$post->id;
        $this->keys = [$counter, $pending, 'views:pending'];
        $this->postJson('/api/views', ['type' => 'post', 'id' => $post->id])->assertNoContent();
        $this->postJson('/api/views', ['type' => 'post', 'id' => $post->id])->assertNoContent();
        $this->assertSame('1', Redis::get($counter));
        $this->assertSame(0, (int) $post->fresh()->views);
        $this->artisan('views:flush')->assertSuccessful();
        $this->assertSame(1, (int) $post->fresh()->views);
        $this->artisan('views:flush')->assertSuccessful();
        $this->assertSame(1, (int) $post->fresh()->views);
        // Simulate a crash after SQL committed but before Redis acknowledged the batch.
        $uuid = (string) Str::uuid();
        DB::table('view_batches')->insert(['id' => $uuid, 'created_at' => now()]);
        Redis::set($pending, $uuid.':7');
        Redis::set($counter, '2');
        Redis::sadd('views:pending', 'post:'.$post->id);
        $this->artisan('views:flush')->assertSuccessful();
        $this->assertSame(1, (int) $post->fresh()->views);
        $this->artisan('views:flush')->assertSuccessful();
        $this->assertSame(3, (int) $post->fresh()->views);
    }

    public function test_draft_and_invalid_view_targets_are_rejected(): void
    {
        $post = Post::factory()->create(['status' => 'draft']);
        $this->postJson('/api/views', ['type' => 'post', 'id' => $post->id])->assertNotFound();
        $this->postJson('/api/views', ['type' => 'user', 'id' => 1])->assertUnprocessable();
    }
}
