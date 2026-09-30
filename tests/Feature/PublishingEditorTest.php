<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Role;
use App\Models\Series;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublishingEditorTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::factory()->create(['name' => 'Super Admin', 'modules' => array_keys(config('modules'))])->id]);
    }

    public function test_composer_has_grouped_fields_and_only_two_creation_modes(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin/import')->assertRedirect('/admin/posts/create?mode=import');
        $this->get('/admin/posts/create?mode=import')->assertOk()
            ->assertSee('Standard article')->assertSee('Split text into chapters')
            ->assertSee('Analyze chapters')->assertSee('Intro/Description')
            ->assertSee('data-choice-search', false)->assertSee('data-image-preview', false)
            ->assertSee('Organization')->assertSee('Search &amp; link', false)
            ->assertDontSee('name="chapter_number"', false)->assertDontSee('Single chapter')
            ->assertDontSee('Author name');
    }

    public function test_creation_defaults_and_admin_actions(): void
    {
        $this->actingAs($this->admin());
        foreach (['posts', 'series', 'pages'] as $resource) {
            $this->get('/admin/'.$resource.'/create')->assertOk()
                ->assertSee('value="published" selected', false);
        }
        $this->get('/admin/posts/create')->assertOk()
            ->assertSee('value="import" checked', false)
            ->assertSee('Publish content')->assertSee('Access management')
            ->assertDontSee('Story library');
        $this->get('/admin/posts/create?mode=normal')->assertOk()
            ->assertSee('value="normal" checked', false);
        $post = Post::factory()->create(['status' => 'draft']);
        $this->get('/admin/posts/'.$post->id.'/edit')->assertOk()
            ->assertSee('value="draft" selected', false);
        $this->get('/admin/posts')->assertOk()
            ->assertSee('title="Edit" aria-label="Edit"', false)
            ->assertSee('title="Delete" aria-label="Delete"', false)
            ->assertSee('title="Copy link" aria-label="Copy link"', false)
            ->assertSee('href="'.post_url($post).'"', false);
    }

    public function test_multiple_categories_are_saved_searchable_and_can_be_cleared(): void
    {
        $this->actingAs($this->admin());
        $categories = Category::factory()->count(2)->create();
        $data = ['title' => 'Grouped publishing', 'content' => '<p><strong>Bold</strong> and <em>italic</em> <u>underlined</u> <s>removed</s>.</p>', 'type' => 'normal', 'status' => 'published', 'category_ids' => $categories->pluck('id')->all(), 'tags' => ['new:Writing'], 'author_name' => 'Must not be collected'];
        $this->post('/admin/posts', $data)->assertSessionHasNoErrors();
        $post = Post::select(['id', 'slug', 'category_id', 'author_name'])->firstOrFail();
        $this->assertNull($post->author_name);
        $this->assertCount(2, $post->categories);
        foreach ($categories as $category) {
            $this->get('/categories/'.$category->slug)->assertOk()->assertSee('Grouped publishing');
        }
        $this->get('/admin/posts/'.$post->id.'/edit')->assertOk()->assertSee('data-copy-link="'.post_url($post).'"', false);
        $this->get('/articles/'.$post->slug)->assertOk()->assertSee('<strong>Bold</strong>', false)->assertSee('<em>italic</em>', false)->assertSee('<u>underlined</u>', false)->assertSee('<s>removed</s>', false)->assertDontSee('Must not be collected');
        unset($data['category_ids']);
        $this->put('/admin/posts/'.$post->id, $data + ['category_selection' => '1'])->assertSessionHasNoErrors();
        $this->assertCount(0, $post->fresh()->categories);
        $this->assertNull($post->fresh()->category_id);
    }

    public function test_inline_analysis_does_not_publish_until_confirmed_and_returns_safe_full_content(): void
    {
        $this->actingAs($this->admin());
        $categories = Category::factory()->count(2)->create();
        $response = $this->postJson('/admin/import/preview', [
            'content' => '<p>My story</p><p>A description</p><h2>CHAPTER 1 - First</h2><p><strong>Formatted body</strong><script>alert(1)</script></p><h2>CHAPTER 2 - Second</h2><p>Second chapter body.</p>',
            'status' => 'published', 'duplicates' => 'skip', 'category_ids' => $categories->pluck('id')->all(), 'tags' => ['new:Story'],
        ])->assertOk()->assertJsonStructure(['token', 'html']);
        $html = $response->json('html');
        $this->assertStringContainsString('<strong>Formatted body</strong>', $html);
        $this->assertStringContainsString('Second chapter body.', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertDatabaseCount('posts', 0);
        $this->assertDatabaseCount('series', 0);
        $this->post('/admin/import', ['token' => $response->json('token')])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseCount('posts', 2);
        $this->assertDatabaseCount('category_post', 4);
        $this->assertDatabaseCount('category_series', 2);
        $series = Series::select(['id', 'slug', 'status'])->firstOrFail();
        $this->get('/stories/'.$series->slug)->assertOk()->assertSee('First')->assertSee('Second');
        $this->post('/admin/import', ['token' => $response->json('token')])->assertStatus(419);
    }

    public function test_import_saves_explicit_series_metadata_and_custom_slug(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin/posts/create')->assertOk()->assertSee('Title &amp; description', false)
            ->assertSee('name="excerpt"', false)->assertSee('name="slug"', false)
            ->assertSee('name="seo_keywords"', false)->assertDontSee('data-standard-seo', false);
        $data = ['title' => 'A separate title', 'description' => 'A separate description',
            'slug' => 'custom-series-link', 'seo_title' => 'Search title', 'seo_keywords' => 'fiction, reading',
            'seo_description' => 'Search description', 'content' => "CHAPTER 1 - First\nChapter body.",
            'status' => 'published', 'duplicates' => 'skip'];
        $response = $this->postJson('/admin/import/preview', $data)->assertOk();
        $this->assertStringContainsString('A separate title', $response->json('html'));
        $this->assertStringContainsString('A separate description', $response->json('html'));
        $this->post('/admin/import', ['token' => $response->json('token')])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('series', ['title' => $data['title'], 'description' => $data['description'],
            'slug' => $data['slug'], 'seo_title' => $data['seo_title'],
            'seo_keywords' => $data['seo_keywords'], 'seo_description' => $data['seo_description']]);
        $this->postJson('/admin/import/preview', $data)->assertUnprocessable()->assertJsonValidationErrors('slug');
        $this->postJson('/admin/import/preview', array_replace($data, ['slug' => 'Invalid Slug']))
            ->assertUnprocessable()->assertJsonValidationErrors('slug');
    }

    public function test_import_generates_a_unique_slug_only_when_left_blank(): void
    {
        $this->actingAs($this->admin());
        $data = ['title' => 'Generated series link', 'slug' => '', 'content' => "CHAPTER 1 - First\nBody.",
            'status' => 'published', 'duplicates' => 'skip'];
        foreach (['generated-series-link', 'generated-series-link-2'] as $slug) {
            $response = $this->postJson('/admin/import/preview', $data)->assertOk();
            $this->post('/admin/import', ['token' => $response->json('token')])->assertRedirect()->assertSessionHasNoErrors();
            $this->assertDatabaseHas('series', ['title' => $data['title'], 'slug' => $slug]);
        }
    }

    public function test_import_preserves_existing_series_slug_and_can_update_metadata(): void
    {
        $this->actingAs($this->admin());
        $series = Series::factory()->create(['slug' => 'existing-series-link']);
        $data = ['series_id' => $series->id, 'title' => 'Updated series title', 'description' => 'Updated description',
            'update_description' => '1', 'seo_title' => 'Updated SEO', 'content' => "CHAPTER 1 - First\nBody.",
            'status' => 'published', 'duplicates' => 'skip'];
        $response = $this->postJson('/admin/import/preview', $data)->assertOk();
        $this->post('/admin/import', ['token' => $response->json('token')])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('series', ['id' => $series->id, 'title' => 'Updated series title',
            'slug' => 'existing-series-link', 'description' => 'Updated description', 'seo_title' => 'Updated SEO']);
    }

    public function test_existing_chapter_keeps_number_and_story_without_manual_inputs(): void
    {
        $this->actingAs($this->admin());
        $story = Series::factory()->create();
        $post = Post::factory()->create(['type' => 'chapter', 'series_id' => $story->id, 'chapter_number' => 7]);
        $post->content()->create(['content' => '<p>Before</p>']);
        $this->get('/admin/posts/'.$post->id.'/edit')->assertOk()->assertSee('Editing chapter 7')->assertDontSee('name="chapter_number"', false);
        $this->put('/admin/posts/'.$post->id, ['title' => 'Edited chapter', 'content' => '<p>After</p>', 'type' => 'normal', 'chapter_number' => 99, 'status' => 'published'])->assertSessionHasNoErrors();
        $this->assertSame(7, $post->fresh()->chapter_number);
        $this->assertSame($story->id, $post->fresh()->series_id);
        $this->assertSame('chapter', $post->fresh()->type);
        $this->post('/admin/posts', ['title' => 'Single chapter', 'content' => '<p>Body</p>', 'type' => 'chapter', 'chapter_number' => 8, 'series_id' => $story->id, 'status' => 'draft'])->assertSessionHasErrors('type');
    }

    public function test_image_upload_and_content_round_trip_keep_relative_storage_paths(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $upload = $this->post('/admin/upload/editor', ['upload' => UploadedFile::fake()->image('editor.png', 40, 40)], ['Accept' => 'application/json'])->assertOk();
        $url = $upload->json('url');
        $this->post('/admin/posts', ['title' => 'Image article', 'type' => 'normal', 'status' => 'published', 'content' => '<p><strong>Photo</strong></p><figure class="image"><img src="'.$url.'" alt="Sample"></figure>', 'image' => UploadedFile::fake()->image('cover.png', 40, 40)])->assertSessionHasNoErrors();
        $post = Post::select(['id', 'slug', 'image'])->with('content')->firstOrFail();
        $this->assertStringNotContainsString('http', $post->image);
        $this->assertStringContainsString('src="/media/editor/', $post->content->content);
        $this->get('/articles/'.$post->slug)->assertOk()->assertSee($url, false)->assertSee('Sample');
        $this->post('/admin/upload/editor', ['upload' => UploadedFile::fake()->create('bad.txt')], ['Accept' => 'application/json'])->assertUnprocessable();
    }

    public function test_analysis_validation_and_permissions_are_enforced(): void
    {
        $role = Role::factory()->create(['modules' => ['pages']]);
        $this->actingAs(User::factory()->create(['role_id' => $role->id]));
        $this->postJson('/admin/import/preview', [])->assertForbidden();
        $this->actingAs($this->admin());
        $this->postJson('/admin/import/preview', ['content' => 'No chapter headings', 'status' => 'published', 'duplicates' => 'skip'])->assertUnprocessable()->assertJsonValidationErrors('content');
        $this->post('/admin/posts', ['title' => 'Invalid category', 'content' => 'Body', 'type' => 'normal', 'status' => 'draft', 'category_ids' => [999999]])->assertSessionHasErrors('category_ids.0');
    }

    public function test_publishing_a_chapter_requires_explicit_publication_of_its_draft_story(): void
    {
        $this->actingAs($this->admin());
        $series = Series::factory()->create(['status' => 'draft']);
        $chapter = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 1, 'status' => 'draft']);
        $other = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 2, 'status' => 'draft']);
        $data = ['title' => 'First chapter', 'content' => '<p>Chapter body</p>', 'status' => 'published'];
        $this->get('/admin/posts/'.$chapter->id.'/edit')->assertSee('Publish the story with this chapter');
        $this->put('/admin/posts/'.$chapter->id, $data)->assertSessionHasErrors('status');
        $this->assertSame('draft', $chapter->fresh()->status);
        $this->put('/admin/posts/'.$chapter->id, $data + ['publish_series' => '1'])->assertSessionHasNoErrors();
        $this->assertSame('published', $series->fresh()->status);
        $this->assertSame('draft', $other->fresh()->status);
        $this->get(post_url($chapter->fresh()))->assertOk()->assertSee('Chapter body');
        $this->get(post_url($other))->assertNotFound();
    }

    public function test_import_can_share_existing_story_cover_and_overwrite_preserves_images(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('series/cover.webp', 'test image');
        $this->actingAs($this->admin());
        $series = Series::factory()->create(['status' => 'published', 'image' => 'series/cover.webp']);
        $data = ['series_id' => $series->id, 'content' => "CHAPTER 1 - First\nFirst body\nCHAPTER 2 - Second\nSecond body", 'status' => 'published', 'duplicates' => 'overwrite', 'share_image' => '1'];
        $preview = $this->postJson('/admin/import/preview', $data)->assertOk();
        $this->assertStringContainsString(media_url('series/cover.webp'), $preview->json('html'));
        $this->post('/admin/import', ['token' => $preview->json('token')])->assertSessionHasNoErrors();
        $chapters = Post::select(['id', 'type', 'slug', 'series_id', 'image'])->where('series_id', $series->id)->with('series')->get();
        $this->assertCount(2, $chapters);
        foreach ($chapters as $chapter) {
            $this->assertSame('series/cover.webp', $chapter->image);
            $this->get(post_url($chapter))->assertOk()->assertSee('<img class="article-image mb-4"', false)->assertSee(media_url('series/cover.webp'), false);
        }
        $data['share_image'] = '0';
        $preview = $this->postJson('/admin/import/preview', $data)->assertOk();
        $this->post('/admin/import', ['token' => $preview->json('token')])->assertSessionHasNoErrors();
        $this->assertSame(2, Post::where('series_id', $series->id)->where('image', 'series/cover.webp')->count());
        Storage::disk('public')->assertExists('series/cover.webp');
    }
}
