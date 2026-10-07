<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Role;
use App\Models\Series;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPostListingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_navigation_lists_posts_instead_of_series_and_includes_chapters_and_standard_articles(): void
    {
        $stories = Category::factory()->create(['slug' => 'stories']);
        $literature = Category::factory()->create(['slug' => 'literature']);
        $series = Series::factory()->create(['title' => 'Series card must not appear', 'category_id' => $stories->id]);
        $chapter = Post::factory()->create(['title' => 'Individual story chapter', 'category_id' => $stories->id, 'series_id' => $series->id, 'type' => 'chapter', 'chapter_number' => 1]);
        $story = Post::factory()->create(['title' => 'Standalone story', 'category_id' => $stories->id]);
        $essay = Post::factory()->create(['title' => 'Literature essay', 'category_id' => $literature->id]);
        $chapter->categories()->attach($literature);
        $essay->categories()->attach($stories);
        $otherChapter = Post::factory()->create(['title' => 'Chapter in another topic', 'series_id' => $series->id, 'type' => 'chapter', 'chapter_number' => 2]);
        $unrelated = Post::factory()->create(['title' => 'Unrelated standard article']);
        $this->get('/stories')->assertOk()->assertSee($chapter->title)->assertSee($story->title)->assertSee($essay->title)
            ->assertSee(post_url($chapter->load('series')), false)->assertDontSee($series->title)->assertDontSee('class="book-card"', false)
            ->assertSee($otherChapter->title)->assertDontSee($unrelated->title)
            ->assertViewHas('posts', fn ($posts) => $posts->total() === 4)->assertViewMissing('series');
        $this->get('/liferature')->assertOk()->assertSee($essay->title)->assertSee($chapter->title)->assertDontSee($story->title)
            ->assertViewHas('posts', fn ($posts) => $posts->total() === 2)->assertViewMissing('series');
        $this->get('/articles')->assertOk()->assertSee($essay->title)->assertSee($chapter->title)->assertSee($story->title)
            ->assertSee($otherChapter->title)->assertSee($unrelated->title)
            ->assertDontSee($series->title)->assertViewHas('posts', fn ($posts) => $posts->total() === 5)->assertViewMissing('series');
    }

    public function test_all_three_post_listings_hide_trash_drafts_future_posts_and_private_chapters_after_cache_invalidation(): void
    {
        config(['responsecache.enabled' => true]);
        $editor = User::factory()->create(['role_id' => Role::factory()->create(['modules' => ['posts']])->id]);
        $stories = Category::factory()->create(['slug' => 'stories']);
        $literature = Category::factory()->create(['slug' => 'liferature']);
        $series = Series::factory()->create();
        $public = Post::factory()->create(['created_by' => $editor->id, 'category_id' => $stories->id, 'type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 1]);
        $public->categories()->attach($literature);
        $draft = Post::factory()->create(['category_id' => $stories->id, 'status' => 'draft']);
        $trash = Post::factory()->create(['category_id' => $stories->id, 'status' => 'bin']);
        $future = Post::factory()->create(['category_id' => $stories->id, 'published_at' => now()->addDay()]);
        $private = Post::factory()->create(['category_id' => $stories->id, 'type' => 'chapter', 'series_id' => Series::factory()->create(['status' => 'draft'])->id, 'chapter_number' => 1]);
        foreach ([$draft, $trash, $future, $private] as $post) $post->categories()->attach($literature);
        foreach (['/stories', '/liferature', '/articles'] as $path) {
            $this->get($path)->assertOk()->assertSee($public->title)
                ->assertDontSee($draft->title)->assertDontSee($trash->title)->assertDontSee($future->title)->assertDontSee($private->title);
        }
        $this->actingAs($editor)->delete('/admin/posts/'.$public->id)->assertRedirect();
        foreach (['/stories', '/liferature', '/articles'] as $path) $this->get($path)->assertOk()->assertDontSee($public->title);
        $this->post('/admin/posts/bulk', ['ids' => [$public->id], 'action' => 'published'])->assertRedirect();
        foreach (['/stories', '/liferature', '/articles'] as $path) $this->get($path)->assertOk()->assertSee($public->title);
    }
}
