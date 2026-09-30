<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Series;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChapterNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sticky_navigation_lists_only_public_chapters_from_the_current_series_in_order(): void
    {
        $series = Series::factory()->create(['status' => 'published']);
        $last = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 3, 'title' => 'Last public chapter', 'status' => 'published', 'published_at' => now()->subDay()]);
        $first = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 1, 'title' => 'First public chapter', 'status' => 'published', 'published_at' => now()->subDay()]);
        $current = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 2, 'status' => 'published', 'published_at' => now()->subDay()]);
        Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 4, 'title' => 'Hidden draft chapter', 'status' => 'draft']);
        Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 5, 'title' => 'Future chapter', 'status' => 'published', 'published_at' => now()->addDay()]);
        Post::factory()->create(['type' => 'chapter', 'series_id' => Series::factory()->create(['status' => 'published'])->id, 'chapter_number' => 1, 'title' => 'Another series chapter', 'status' => 'published']);

        $this->get(post_url($current))->assertOk()
            ->assertSee('data-chapter-sticky', false)->assertSee('Currently reading')
            ->assertSee('Chapter 2')->assertSee('aria-current="page"', false)
            ->assertSee('href="'.post_url($first).'"', false)
            ->assertSee('href="'.post_url($last).'"', false)
            ->assertDontSee('Hidden draft chapter')->assertDontSee('Future chapter')
            ->assertDontSee('Another series chapter')->assertDontSee('data-copy-link', false)
            ->assertViewHas('chapterLinks', fn ($links) => $links->pluck('id')->all() === [$first->id, $current->id, $last->id]);
    }

    public function test_single_chapter_has_no_previous_or_next_link(): void
    {
        $series = Series::factory()->create(['status' => 'published']);
        $chapter = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 1, 'status' => 'published', 'published_at' => now()->subDay()]);
        $this->get(post_url($chapter))->assertOk()->assertSee('data-chapter-sticky', false)
            ->assertDontSee('title="Previous chapter"', false)->assertDontSee('title="Next chapter"', false);
        $article = Post::factory()->create(['type' => 'normal', 'series_id' => null, 'status' => 'published', 'published_at' => now()->subDay()]);
        $this->get(post_url($article))->assertOk()->assertDontSee('data-chapter-sticky', false);
    }
}
