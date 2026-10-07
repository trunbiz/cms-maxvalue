<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Role;
use App\Models\Series;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTrashTest extends TestCase
{
    use RefreshDatabase;

    private function editor(): User
    {
        return User::factory()->create(['role_id' => Role::factory()->create(['modules' => ['posts']])->id]);
    }

    public function test_admin_hides_trash_by_default_and_sorts_search_results_after_active_posts(): void
    {
        $editor = $this->editor();
        $published = Post::factory()->create(['created_by' => $editor->id, 'title' => 'Matching published']);
        $draft = Post::factory()->create(['created_by' => $editor->id, 'title' => 'Matching draft', 'status' => 'draft']);
        $bin = Post::factory()->create(['created_by' => $editor->id, 'title' => 'Matching trash', 'status' => 'bin']);
        $other = Post::factory()->create(['created_by' => $this->editor()->id, 'title' => 'Matching other trash', 'status' => 'bin']);
        $this->actingAs($editor)->get('/admin/posts')->assertOk()
            ->assertSee($published->title)->assertSee($draft->title)->assertDontSee($bin->title)
            ->assertViewHas('records', fn ($records) => $records->total() === 2);
        $this->get('/admin/posts?created_by='.$editor->id)->assertOk()->assertDontSee($bin->title);
        $this->get('/admin/posts?q=Matching')->assertOk()->assertSee($bin->title)->assertDontSee($other->title)
            ->assertViewHas('records', fn ($records) => $records->pluck('id')->all() === [$draft->id, $published->id, $bin->id]);
        $this->get('/admin/posts?status=bin')->assertOk()->assertSee($bin->title)->assertDontSee($published->title)->assertDontSee($draft->title);
        $this->get('/admin/posts?q=Matching&status=draft')->assertOk()->assertSee($draft->title)->assertDontSee($bin->title)->assertDontSee($published->title);
    }

    public function test_deleting_chapters_clears_cached_home_and_hides_a_story_with_no_public_chapters(): void
    {
        config(['responsecache.enabled' => true]);
        $editor = $this->editor();
        $series = Series::factory()->create(['created_by' => $editor->id, 'title' => 'Story to remove from home']);
        $first = Post::factory()->create(['created_by' => $editor->id, 'series_id' => $series->id, 'type' => 'chapter', 'chapter_number' => 1, 'title' => 'First public scene']);
        $last = Post::factory()->create(['created_by' => $editor->id, 'series_id' => $series->id, 'type' => 'chapter', 'chapter_number' => 2, 'title' => 'Last public scene']);
        Post::factory()->create(['series_id' => $series->id, 'type' => 'chapter', 'chapter_number' => 3, 'status' => 'draft']);
        Post::factory()->create(['series_id' => $series->id, 'type' => 'chapter', 'chapter_number' => 4, 'published_at' => now()->addDay()]);
        $this->get('/')->assertOk()->assertSee($first->title)->assertSee($last->title)->assertSee($series->title);
        $this->actingAs($editor)->delete('/admin/posts/'.$first->id)->assertRedirect('/admin/posts');
        $this->get('/')->assertOk()->assertDontSee($first->title)->assertSee($last->title)->assertSee($series->title);
        $this->delete('/admin/posts/'.$last->id)->assertRedirect('/admin/posts');
        $this->get('/')->assertOk()->assertDontSee($first->title)->assertDontSee($last->title)->assertDontSee($series->title);
        $this->get(post_url($last->load('series')))->assertNotFound();
        $this->assertDatabaseHas('series', ['id' => $series->id, 'status' => 'published']);
        $this->post('/admin/posts/bulk', ['ids' => [$last->id], 'action' => 'published'])->assertRedirect();
        $this->get('/')->assertOk()->assertSee($last->title)->assertSee($series->title);
    }

    public function test_bulk_trash_removes_cached_chapters_and_empty_stories_from_home(): void
    {
        config(['responsecache.enabled' => true]);
        $editor = $this->editor();
        $series = Series::factory()->create(['created_by' => $editor->id, 'title' => 'Bulk removed story']);
        $chapter = Post::factory()->create(['created_by' => $editor->id, 'series_id' => $series->id, 'type' => 'chapter', 'chapter_number' => 1, 'title' => 'Bulk removed scene']);
        $this->get('/')->assertOk()->assertSee($chapter->title)->assertSee($series->title);
        $this->actingAs($editor)->post('/admin/posts/bulk', ['ids' => [$chapter->id], 'action' => 'bin'])->assertRedirect();
        $this->get('/')->assertOk()->assertDontSee($chapter->title)->assertDontSee($series->title);
        $this->get('/admin/posts')->assertOk()->assertDontSee($chapter->title);
        $this->get('/admin/posts?q=Bulk')->assertOk()->assertSee($chapter->title);
    }
}
