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
            ->assertSee('Analyze chapters')->assertSee('Manuscript text')
            ->assertSee('Paste a manuscript and save. Preview is optional.')
            ->assertSee('data-choice-search', false)->assertSee('data-image-preview', false)
            ->assertSee('Organization')->assertDontSee('data-search-link', false)
            ->assertDontSee('name="series_id"', false)->assertDontSee('data-tag-picker', false)
            ->assertDontSee('href="/admin/series"', false)
            ->assertSee('name="share_image" value="1"', false)->assertDontSee('Use this cover for all imported chapters')
            ->assertDontSee('name="chapter_number"', false)->assertDontSee('Single chapter')
            ->assertDontSee('Author name');
    }

    public function test_creation_defaults_and_admin_actions(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin/series/create')->assertOk()->assertSee('value="published" selected', false);
        $this->get('/admin/posts/create')->assertOk()->assertSee('name="status" value="published"', false)
            ->assertDontSee('id="field-status"', false)->assertSee('data-save-draft', false);
        $this->get('/admin/posts/create')->assertOk()
            ->assertSee('value="import" checked', false)
            ->assertSee('Publish content')->assertSee('Access management')
            ->assertDontSee('Story library');
        $this->get('/admin/posts/create?mode=normal')->assertOk()
            ->assertSee('value="normal" checked', false);
        $post = Post::factory()->create(['status' => 'draft']);
        $this->get('/admin/posts/'.$post->id.'/edit')->assertOk()
            ->assertSee('name="status" value="draft"', false)->assertDontSee('id="field-status"', false);
        $this->get('/admin/posts')->assertOk()
            ->assertSee('title="Edit" aria-label="Edit"', false)
            ->assertSee('title="Delete" aria-label="Delete"', false)
            ->assertSee('title="Copy link" aria-label="Copy link"', false)
            ->assertSee('data-copy-link="'.post_url($post).'"', false);
    }

    public function test_publish_and_copy_returns_the_saved_public_link(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin/posts/create?mode=normal')->assertOk()
            ->assertSee('Publish and copy link')->assertSee('data-mode-key', false)
            ->assertDontSee('New content is published by default. Use Save draft to keep it private.');
        $response = $this->postJson('/admin/posts', ['title' => 'Copy published article', 'type' => 'normal', 'content' => '<p>Public body</p>', 'status' => 'published']);
        $post = Post::where('title', 'Copy published article')->firstOrFail();
        $response->assertCreated()->assertJson(['url' => post_url($post), 'edit_url' => '/admin/posts/'.$post->id.'/edit']);
        $this->get(post_url($post))->assertOk()->assertSee('Public body');
        $this->get('/admin/posts/'.$post->id.'/edit')->assertOk()->assertSee('Title &amp; image', false);
        $this->postJson('/admin/posts', ['title' => '', 'type' => 'normal'])->assertUnprocessable();
    }

    public function test_edit_form_hides_publishing_and_tags_and_save_copy_preserves_existing_metadata(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        foreach (['draft', 'published', 'bin'] as $status) {
            $post = Post::factory()->create(['created_by' => $admin->id, 'status' => $status, 'published_at' => '2026-09-01 12:00:00']);
            $tag = \App\Models\Tag::factory()->create();
            $post->tags()->attach($tag);
            $this->get('/admin/posts/'.$post->id.'/edit')->assertOk()
                ->assertSee('Save and copy link')->assertSee('Title &amp; image', false)
                ->assertDontSee('id="field-status"', false)->assertDontSee('name="tag_selection"', false)
                ->assertDontSee('id="field-published_at"', false);
            $data = ['title' => 'Updated '.$status, 'slug' => $post->slug, 'content' => '<p>Updated body</p>', 'type' => 'normal', 'status' => $status, 'published_at' => '2026-09-01 12:00:00'];
            $response = $this->putJson('/admin/posts/'.$post->id, $data)->assertOk();
            $post->refresh();
            $response->assertJson(['url' => post_url($post), 'edit_url' => '/admin/posts/'.$post->id.'/edit']);
            $this->assertSame($status, $post->status);
            $this->assertSame('2026-09-01 12:00:00', $post->published_at->format('Y-m-d H:i:s'));
            $this->assertSame([$tag->id], $post->tags->pluck('id')->all());
            $this->put('/admin/posts/'.$post->id, $data)->assertRedirect('/admin/posts')->assertSessionHasNoErrors();
            $this->assertSame([$tag->id], $post->fresh()->tags->pluck('id')->all());
        }
    }

    public function test_new_manuscripts_require_title_and_new_posts_default_to_stories(): void
    {
        $this->actingAs($this->admin());
        $this->postJson('/admin/import/save', ['content' => "Fallback title\nCHAPTER 1 - First\nBody", 'duplicates' => 'skip'])
            ->assertUnprocessable()->assertJsonValidationErrors('title');
        $this->assertDatabaseCount('series', 0);
        $this->post('/admin/posts', ['title' => 'Default category article', 'content' => 'Body', 'type' => 'normal', 'category_selection' => '1'])
            ->assertRedirect('/admin/posts')->assertSessionHasNoErrors();
        $stories = Category::where('slug', 'stories')->firstOrFail();
        $article = Post::where('title', 'Default category article')->firstOrFail();
        $this->assertSame($stories->id, $article->category_id);
        $this->assertSame([$stories->id], $article->categories->modelKeys());
        $this->post('/admin/import/save', ['title' => 'Default category story', 'content' => "CHAPTER 1 - First\nBody", 'duplicates' => 'skip', 'category_selection' => '1'])
            ->assertRedirect('/admin/posts')->assertSessionHasNoErrors();
        $series = Series::where('title', 'Default category story')->firstOrFail();
        $this->assertSame($stories->id, $series->category_id);
        $this->assertSame([$stories->id], $series->categories->modelKeys());
        $chapter = Post::where('series_id', $series->id)->firstOrFail();
        $this->assertSame($stories->id, $chapter->category_id);
        $this->assertSame([$stories->id], $chapter->categories->modelKeys());
    }

    public function test_manuscripts_create_new_stories_and_share_the_cover_by_default(): void
    {
        $this->actingAs($this->admin());
        $path = 'imports/shared-cover.webp';
        $data = ['title' => 'New story', 'content' => "CHAPTER 1 - First\nFirst body\nCHAPTER 2 - Second\nSecond body", 'image_path' => $path, 'duplicates' => 'skip'];
        for ($import = 0; $import < 2; $import++) {
            $this->withSession(['featured_uploads' => [$path]])->post('/admin/import/save', $data)
                ->assertRedirect('/admin/posts')->assertSessionHasNoErrors();
        }
        $this->assertSame(2, Series::where('title', 'New story')->count());
        $this->assertSame(4, Post::where('type', 'chapter')->where('image', $path)->count());
        $this->assertSame(2, Post::where('type', 'chapter')->distinct()->count('series_id'));
    }

    public function test_post_creation_and_chapter_import_publish_by_default_and_support_drafts(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin/posts/create?mode=normal')->assertOk()->assertSee('data-standard-required', false)
            ->assertSee('aria-required="true"', false)->assertSee('admin-heading-back', false)
            ->assertSee('Save draft')->assertDontSee('id="field-status"', false);
        $data = ['title' => 'Default public article', 'type' => 'normal', 'content' => '<p>Article body</p>'];
        $this->post('/admin/posts', $data)->assertRedirect('/admin/posts')->assertSessionHasNoErrors();
        $article = Post::where('title', $data['title'])->firstOrFail();
        $this->assertSame('published', $article->status);
        $this->assertNotNull($article->published_at);
        $this->put('/admin/posts/'.$article->id, $data + ['status' => 'draft'])->assertRedirect('/admin/posts')->assertSessionHasNoErrors();
        $this->assertSame('draft', $article->fresh()->status);
        $this->post('/admin/posts', ['title' => 'Private article', 'type' => 'normal', 'status' => 'draft', 'content' => '<p>Private body</p>'])
            ->assertRedirect('/admin/posts')->assertSessionHasNoErrors();
        $this->assertDatabaseHas('posts', ['title' => 'Private article', 'status' => 'draft']);
        foreach (['published', 'draft'] as $status) {
            $import = ['title' => 'Story '.$status, 'content' => "CHAPTER 1 - First $status\nChapter body", 'duplicates' => 'skip'];
            if ($status === 'draft') $import['status'] = 'draft';
            $this->post('/admin/import/save', $import)->assertRedirect('/admin/posts')->assertSessionHasNoErrors();
            $this->assertDatabaseHas('series', ['title' => 'Story '.$status, 'status' => $status]);
            $this->assertDatabaseHas('posts', ['title' => 'First '.$status, 'status' => $status]);
        }
    }

    public function test_importing_drafts_does_not_unpublish_existing_public_chapters(): void
    {
        $this->actingAs($this->admin());
        $series = Series::factory()->create(['status' => 'published']);
        $published = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 1]);
        $this->post('/admin/import/save', ['series_id' => $series->id, 'status' => 'draft', 'content' => "CHAPTER 2 - Private second chapter\nPrivate content", 'duplicates' => 'skip'])
            ->assertRedirect('/admin/posts')->assertSessionHasNoErrors();
        $this->assertSame('published', $series->fresh()->status);
        $this->get(post_url($published))->assertOk();
        $draft = Post::where('title', 'Private second chapter')->firstOrFail();
        $this->assertSame('draft', $draft->status);
        $this->get(post_url($draft))->assertNotFound();
    }

    public function test_simplified_create_form_keeps_slug_and_reveals_its_validation_errors(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin/posts/create?mode=normal')->assertOk()
            ->assertSee('data-composer data-auto-slug', false)
            ->assertDontSee('data-description-options', false)->assertDontSee('name="excerpt"', false)
            ->assertSee('data-title-slug', false)
            ->assertDontSee('data-slug-options', false)
            ->assertSee('Generated as you type the title. You can edit it directly.')
            ->assertDontSee('data-search-link', false);
        $this->withSession(['errors' => (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag([
            'excerpt' => 'Invalid description', 'slug' => 'Invalid slug', 'seo_title' => 'Invalid SEO title',
        ]))])->get('/admin/posts/create?mode=normal')->assertOk()
            ->assertDontSee('data-description-options', false)
            ->assertSee('Invalid slug')
            ->assertDontSee('data-search-link', false);
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
            'title' => 'My story',
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
        $first = Post::where('series_id', $series->id)->where('chapter_number', 1)->firstOrFail();
        $this->get('/stories/'.$series->slug)->assertRedirect(post_url($first));
        $this->get(post_url($first))->assertOk()->assertSee('First')->assertSee('Second');
        $this->post('/admin/import', ['token' => $response->json('token')])->assertStatus(419);
    }

    public function test_import_saves_explicit_series_metadata_and_custom_slug(): void
    {
        $this->actingAs($this->admin());
        $this->get('/admin/posts/create')->assertOk()->assertSee('Title &amp; image', false)
            ->assertDontSee('name="excerpt"', false)->assertSee('name="slug"', false)
            ->assertDontSee('name="seo_keywords"', false)->assertDontSee('data-standard-seo', false);
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
        $duplicate = $this->postJson('/admin/import/preview', $data)->assertOk();
        $this->post('/admin/import', ['token' => $duplicate->json('token')])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('series', ['slug' => 'custom-series-link-2']);
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
        $this->postJson('/admin/import/preview', ['title' => 'Invalid manuscript', 'content' => 'No chapter headings', 'status' => 'published', 'duplicates' => 'skip'])->assertUnprocessable()->assertJsonValidationErrors('content');
        $this->post('/admin/posts', ['title' => 'Invalid category', 'content' => 'Body', 'type' => 'normal', 'status' => 'draft', 'category_ids' => [999999]])->assertSessionHasErrors('category_ids.0');
    }

    public function test_publishing_a_chapter_requires_explicit_publication_of_its_draft_story(): void
    {
        $this->actingAs($this->admin());
        $series = Series::factory()->create(['status' => 'draft']);
        $chapter = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 1, 'status' => 'draft']);
        $other = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 2, 'status' => 'draft']);
        $data = ['title' => 'First chapter', 'content' => '<p>Chapter body</p>', 'status' => 'published'];
        $this->get('/admin/posts/'.$chapter->id.'/edit')->assertDontSee('id="field-status"', false)
            ->assertSee('name="status" value="draft"', false)->assertSee('Save and copy link');
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
