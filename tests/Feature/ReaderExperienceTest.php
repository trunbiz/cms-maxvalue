<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReaderExperienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_features_the_six_latest_public_chapters_with_direct_reading_links(): void
    {
        $series = \App\Models\Series::factory()->create(['title' => 'Featured story']);
        $chapters = collect();
        for ($number = 1; $number <= 7; $number++) {
            $chapters->push(Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => $number, 'published_at' => now()->subDays(8 - $number)]));
        }
        $article = Post::factory()->create(['title' => 'Standalone article']);
        $draft = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'status' => 'draft', 'chapter_number' => 8]);
        $future = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'published_at' => now()->addDay(), 'chapter_number' => 9]);
        $hiddenSeries = \App\Models\Series::factory()->create(['status' => 'draft']);
        $hidden = Post::factory()->create(['type' => 'chapter', 'series_id' => $hiddenSeries->id, 'chapter_number' => 1]);
        $this->get('/')->assertOk()->assertSee('READ & REFLECT', false)->assertSee('Latest chapters')->assertSee('Featured story')
            ->assertSee('href="'.post_url($chapters->last()).'"', false)->assertSee('Chapter 7')
            ->assertDontSee($article->title)->assertDontSee($draft->title)->assertDontSee($future->title)->assertDontSee($hidden->title)
            ->assertViewHas('posts', fn ($posts) => $posts->pluck('id')->all() === $chapters->reverse()->take(6)->pluck('id')->all());
    }

    public function test_home_has_no_hero_and_navigation_marks_the_current_section(): void
    {
        $this->get('/')->assertOk()->assertDontSee('class="reading-hero', false)
            ->assertSee('class="nav-link active" href="/" aria-current="page"', false);
        $this->get('/articles')->assertOk()
            ->assertSee('class="nav-link active" href="/articles" aria-current="page"', false)
            ->assertDontSee('class="nav-link active" href="/"', false);
        $post = Post::factory()->create();
        $this->get(post_url($post))->assertOk()
            ->assertSee('class="nav-link active" href="/articles" aria-current="location"', false)
            ->assertDontSee('class="nav-link active" href="/"', false);
    }

    public function test_search_matches_partial_and_non_adjacent_title_words(): void
    {
        $post = Post::factory()->create(['title' => 'A guide to reading wonderful stories']);
        $draft = Post::factory()->create(['title' => 'Reading stories draft', 'status' => 'draft']);
        foreach (['read', 'GUIDE stor', 'reading stories'] as $query) {
            $this->get('/search?q='.urlencode($query))->assertOk()->assertSee($post->title)->assertDontSee($draft->title);
        }
        $this->get('/search?q=%25')->assertOk()->assertDontSee($post->title);
        $this->get('/search')->assertOk()->assertDontSee($post->title);
    }

    public function test_related_articles_prioritize_tags_and_secondary_categories_then_fill_to_ten(): void
    {
        $article = Post::factory()->create();
        $tag = Tag::create(['name' => 'Reading', 'slug' => 'reading']);
        $article->tags()->attach($tag);
        $tagMatch = Post::factory()->create(['published_at' => now()->subDays(3)]);
        $tagMatch->tags()->attach($tag);
        $category = Category::factory()->create();
        $article->categories()->attach($category);
        $categoryMatch = Post::factory()->create(['published_at' => now()->subDays(2)]);
        $categoryMatch->categories()->attach($category);
        Post::factory()->count(12)->create();
        $draft = Post::factory()->create(['status' => 'draft', 'category_id' => $article->category_id]);
        $future = Post::factory()->create(['published_at' => now()->addDay(), 'category_id' => $article->category_id]);
        $this->get(post_url($article))->assertOk()->assertViewHas('related', function ($related) use ($article, $tagMatch, $categoryMatch, $draft, $future) {
            $ids = $related->pluck('id');
            return $ids->count() === 10 && $ids->unique()->count() === 10
                && $ids->take(2)->contains($tagMatch->id) && $ids->take(2)->contains($categoryMatch->id)
                && ! $ids->contains($article->id) && ! $ids->contains($draft->id) && ! $ids->contains($future->id);
        });
    }

    public function test_unrelated_articles_are_used_when_no_topics_match(): void
    {
        $article = Post::factory()->create(['category_id' => null]);
        Post::factory()->count(12)->create();
        $this->get(post_url($article))->assertOk()->assertViewHas('related', fn ($related) => $related->count() === 10);
    }

    public function test_missing_content_keeps_404_status_and_suggests_only_public_articles(): void
    {
        $post = Post::factory()->create();
        $draft = Post::factory()->create(['status' => 'draft']);
        foreach (['/missing-content', '/articles/missing-content', '/categories/missing-content'] as $path) {
            $this->get($path)->assertNotFound()->assertSee('Content not found')->assertSee('Articles you might enjoy')
                ->assertSee($post->title)->assertDontSee($draft->title)->assertHeader('X-Robots-Tag', 'noindex, follow');
        }
        $this->getJson('/articles/missing-content')->assertNotFound()->assertDontSee('Articles you might enjoy');
    }

    public function test_404_suggests_public_chapters_when_there_are_no_articles(): void
    {
        $series = \App\Models\Series::factory()->create();
        $chapter = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 1]);
        $hiddenSeries = \App\Models\Series::factory()->create(['status' => 'draft']);
        $hidden = Post::factory()->create(['type' => 'chapter', 'series_id' => $hiddenSeries->id, 'chapter_number' => 1]);
        $this->get('/missing-content')->assertNotFound()->assertSee('not-found-code', false)
            ->assertSee('Articles you might enjoy')->assertSee($chapter->title)
            ->assertSee(post_url($chapter))->assertDontSee($hidden->title);
    }
}
