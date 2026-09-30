<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Series;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ReadTrackingDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_have_no_read_counters_or_tracking_hooks(): void
    {
        $series = Series::factory()->create(['status' => 'published']);
        $chapter = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id,
            'chapter_number' => 1, 'status' => 'published', 'published_at' => now()->subDay()]);
        $article = Post::factory()->create(['type' => 'normal', 'series_id' => null,
            'status' => 'published', 'published_at' => now()->subDay()]);
        foreach (['/', '/stories/'.$series->slug, post_url($chapter), post_url($article)] as $url) {
            $this->get($url)->assertOk()->assertDontSee('data-view-type', false)
                ->assertDontSee('data-view-id', false)->assertDontSee(' reads');
        }
        $this->assertStringNotContainsString('/api/views', file_get_contents(resource_path('js/app.js')));
    }

    public function test_tracking_endpoint_and_flush_command_are_removed(): void
    {
        $this->postJson('/api/views', ['type' => 'post', 'id' => 1])->assertNotFound();
        $this->assertArrayNotHasKey('views:flush', Artisan::all());
        foreach (app(\Illuminate\Console\Scheduling\Schedule::class)->events() as $event) {
            $this->assertStringNotContainsString('views:flush', $event->command ?? '');
        }
    }
}
