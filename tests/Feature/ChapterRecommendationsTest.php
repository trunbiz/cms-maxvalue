<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Series;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChapterRecommendationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_chapter_recommendations_use_series_topics_and_fill_to_ten(): void
    {
        $series = Series::factory()->create();
        $chapter = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 1]);
        $tag = Tag::create(['name' => 'Shared topic', 'slug' => 'shared-topic']);
        $series->tags()->attach($tag);
        $otherSeries = Series::factory()->create();
        $otherSeries->tags()->attach($tag);
        $tagMatch = Post::factory()->create(['type' => 'chapter', 'series_id' => $otherSeries->id, 'chapter_number' => 1, 'published_at' => now()->subDays(3)]);
        $categoryMatch = Post::factory()->create(['category_id' => $series->category_id, 'published_at' => now()->subDays(2)]);
        Post::factory()->count(12)->create();
        $draft = Post::factory()->create(['category_id' => $series->category_id, 'status' => 'draft']);
        $future = Post::factory()->create(['category_id' => $series->category_id, 'published_at' => now()->addDay()]);
        $hiddenSeries = Series::factory()->create(['status' => 'draft']);
        $hidden = Post::factory()->create(['type' => 'chapter', 'series_id' => $hiddenSeries->id, 'chapter_number' => 1]);
        $this->get(post_url($chapter))->assertOk()->assertSee('Related articles')->assertSee(post_url($tagMatch))
            ->assertViewHas('related', function ($related) use ($chapter, $tagMatch, $categoryMatch, $draft, $future, $hidden) {
                $ids = $related->pluck('id');
                return $ids->count() === 10 && $ids->unique()->count() === 10
                    && $ids->take(2)->contains($tagMatch->id) && $ids->take(2)->contains($categoryMatch->id)
                    && $ids->intersect([$chapter->id, $draft->id, $future->id, $hidden->id])->isEmpty();
            });
    }

    public function test_chapters_without_matching_topics_get_ten_other_public_chapters(): void
    {
        $series = Series::factory()->create(['category_id' => null]);
        $chapter = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'category_id' => null, 'chapter_number' => 1]);
        for ($number = 2; $number <= 13; $number++) {
            Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'category_id' => null, 'chapter_number' => $number]);
        }
        $this->get(post_url($chapter))->assertOk()->assertSee('Related articles')
            ->assertViewHas('related', fn ($related) => $related->count() === 10 && ! $related->pluck('id')->contains($chapter->id));
    }
}
