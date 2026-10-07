<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminUpdatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_posts_date_filters_include_boundary_days_and_keep_ownership_scope(): void
    {
        $staff = $this->staff();
        $first = Post::factory()->create(['created_by' => $staff->id, 'created_at' => '2026-09-15 00:00:00']);
        $last = Post::factory()->create(['created_by' => $staff->id, 'created_at' => '2026-09-16 23:59:59']);
        $before = Post::factory()->create(['created_by' => $staff->id, 'created_at' => '2026-09-14 23:59:59']);
        $after = Post::factory()->create(['created_by' => $staff->id, 'created_at' => '2026-09-17 00:00:00']);
        $other = Post::factory()->create(['created_by' => $this->staff()->id, 'created_at' => '2026-09-15 12:00:00']);
        $this->actingAs($staff)->get('/admin/posts?created_from=2026-09-15&created_until=2026-09-16')->assertOk()
            ->assertSee('Add new')->assertSee('name="created_from"', false)->assertSee('name="created_until"', false)
            ->assertSee($first->title)->assertSee($last->title)->assertDontSee($before->title)->assertDontSee($after->title)->assertDontSee($other->title)
            ->assertViewHas('records', fn ($records) => $records->total() === 2 && str_contains($records->url(2), 'created_from=2026-09-15') && str_contains($records->url(2), 'created_until=2026-09-16'));
        $this->get('/admin/posts?created_from=2026-09-17')->assertOk()->assertSee($after->title)->assertDontSee($last->title);
        $this->get('/admin/posts?created_until=2026-09-14')->assertOk()->assertSee($before->title)->assertDontSee($first->title);
        $this->get('/admin/posts?created_from=bad-date')->assertUnprocessable();
        $this->get('/admin/posts?created_from=2026-09-17&created_until=2026-09-15')->assertUnprocessable();
    }

    public function test_posts_list_displays_creation_time_and_eager_loaded_creator(): void
    {
        $staff = $this->staff();
        $post = Post::factory()->create(['created_by' => $staff->id, 'created_at' => '2026-09-15 09:35:00']);
        $response = $this->actingAs($staff)->get('/admin/posts');
        $response->assertOk()->assertSee('admin-posts-table', false)->assertSee('post-selection-target', false)
            ->assertSee('15/09/2026')->assertSee('09:35')->assertSee($staff->name)
            ->assertViewHas('records', function ($records) use ($post, $staff) {
                $record = $records->firstWhere('id', $post->id);
                return $record->relationLoaded('creator') && $record->creator->id === $staff->id;
            });
        $post->update(['created_by' => null]);
        $this->actingAs(User::factory()->create(['role_id' => Role::firstOrCreate(['name' => 'Super Admin'], ['modules' => []])->id]))
            ->get('/admin/posts')->assertOk()->assertSee($post->title);
    }

    private function staff(): User
    {
        return User::factory()->create(['name' => 'Nguyễn Văn An', 'role_id' => Role::firstOrCreate(['name' => 'Employee'], ['modules' => ['posts', 'dashboard']])->id]);
    }

    public function test_staff_can_only_access_own_posts_and_move_them_to_bin(): void
    {
        $staff = $this->staff();
        $own = Post::factory()->create(['created_by' => $staff->id]);
        $other = Post::factory()->create(['title' => 'Private other article', 'created_by' => $this->staff()->id]);
        $this->actingAs($staff)->get('/admin/posts')->assertOk()->assertSee($own->title)->assertDontSee($other->title);
        $this->get('/admin/posts/'.$other->id.'/edit')->assertNotFound();
        $this->get('/admin/posts/'.$other->id.'/preview')->assertForbidden();
        $this->put('/admin/posts/'.$other->id, ['title' => 'Unauthorized edit', 'type' => 'normal', 'status' => 'published', 'content' => 'Other body'])->assertNotFound();
        $this->delete('/admin/posts/'.$other->id)->assertNotFound();
        $this->delete('/admin/posts/'.$own->id)->assertRedirect();
        $this->assertDatabaseHas('posts', ['id' => $own->id, 'status' => 'bin']);
    }

    public function test_slugs_have_author_suffix_and_random_collision_suffix(): void
    {
        $staff = $this->staff(); $this->actingAs($staff);
        $data = ['title' => 'Tên bài viết', 'type' => 'normal', 'status' => 'published', 'content' => '<p>Body</p>'];
        $this->post('/admin/posts', $data)->assertRedirect();
        $this->post('/admin/posts', $data)->assertRedirect();
        $posts = Post::orderBy('id')->get();
        $this->assertSame('ten-bai-viet/nguyen-van-an', $posts[0]->slug);
        $this->assertMatchesRegularExpression('~^ten-bai-viet-\d+/nguyen-van-an$~', $posts[1]->slug);
        $this->assertSame($staff->id, $posts[0]->created_by);
        $this->get('/articles/'.$posts[0]->slug)->assertOk();
    }

    public function test_manual_slug_overrides_settings_and_survives_editing(): void
    {
        $this->actingAs($this->staff());
        \App\Models\Setting::updateOrCreate(['key' => 'permalink_structure'], ['value' => 'day']);
        app(\App\Services\CacheInvalidator::class)->invalidate();
        $data = ['title' => 'Custom article', 'slug' => 'my-link', 'type' => 'normal', 'content' => '<p>Custom body</p>', 'status' => 'published'];
        $this->post('/admin/posts', $data)->assertSessionHasNoErrors();
        $post = Post::where('title', 'Custom article')->firstOrFail();
        $this->assertSame('my-link', $post->slug);
        $this->assertTrue($post->slug_is_custom);
        $this->assertSame(url('/my-link/'), post_url($post));
        $this->get('/my-link/')->assertOk()->assertSee('Custom body');
        $this->get('/articles')->assertOk()->assertSee(post_url($post), false);
        $this->put('/admin/posts/'.$post->id, array_replace($data, ['title' => 'Renamed']))->assertSessionHasNoErrors();
        $this->assertSame('my-link', $post->fresh()->slug);
        $this->assertTrue($post->fresh()->slug_is_custom);
        $this->post('/admin/posts', array_replace($data, ['title' => 'Duplicate']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('posts', ['title' => 'Duplicate', 'slug' => 'my-link-2', 'slug_is_custom' => true]);
        $this->put('/admin/posts/'.$post->id, array_replace($data, ['slug' => '']))->assertSessionHasNoErrors();
        $post->refresh();
        $this->assertFalse($post->slug_is_custom);
        $this->assertStringContainsString(now()->format('/Y/m/d/'), post_url($post));
        $this->get(post_url($post))->assertOk();
    }

    public function test_posts_list_prefixes_chapters_and_keeps_searchable_filters_selected(): void
    {
        $staff = $this->staff();
        $series = \App\Models\Series::factory()->create(['created_by' => $staff->id]);
        $category = \App\Models\Category::factory()->create();
        $chapter = Post::factory()->create(['created_by' => $staff->id, 'type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 4, 'title' => 'Fourth scene']);
        $chapter->categories()->attach($category);
        $normal = Post::factory()->create(['created_by' => $staff->id, 'title' => 'Standard article']);
        $this->actingAs($staff)->get('/admin/posts')->assertOk()->assertSee('Chapter 4: Fourth scene')->assertSee($normal->title);
        $this->get('/admin/posts?'.http_build_query(['category_id' => $category->id, 'created_by' => $staff->id, 'series_id' => $series->id]))
            ->assertOk()->assertSee('Chapter 4: Fourth scene')->assertDontSee($normal->title)
            ->assertSee('data-search-label="Search categories..."', false)
            ->assertSee('data-search-label="Search creators..."', false)
            ->assertSee('data-search-label="Search stories..."', false)
            ->assertViewHas('records', fn ($records) => $records->total() === 1);
    }

    public function test_featured_image_is_uploaded_before_save_and_path_is_owned_by_session(): void
    {
        Storage::fake('public'); config(['cloudflare.media_disk' => 'public']);
        $this->actingAs($this->staff());
        $upload = $this->postJson('/admin/upload/featured', ['upload' => UploadedFile::fake()->image('cover.jpg')])->assertOk();
        $path = $upload->json('path'); Storage::disk('public')->assertExists($path);
        $data = ['title' => 'Photo article', 'type' => 'normal', 'status' => 'published', 'content' => 'Body', 'image_path' => $path];
        $this->post('/admin/posts', $data)->assertRedirect();
        $this->assertDatabaseHas('posts', ['image' => $path]);
        $data['image_path'] = 'posts/foreign.webp';
        $this->post('/admin/posts', $data)->assertStatus(422);
    }

    public function test_admin_language_switch_leaves_content_and_frontend_language_intact(): void
    {
        $this->actingAs($this->staff())->withSession(['admin_locale' => null])->get('/admin/posts/create')->assertOk()->assertSee('Phân tích chương')->assertSee('Nguyễn Văn An');
        $this->post('/admin/language', ['language' => 'en'])->assertRedirect();
        $this->get('/admin/posts/create')->assertSee('Analyze chapters');
        $this->post('/admin/language', ['language' => 'fr'])->assertSessionHasErrors('language');
        $this->get('/')->assertOk()->assertSee('<html lang="en"', false);
    }

    public function test_direct_import_records_author_and_denies_overwriting_another_story(): void
    {
        $staff = $this->staff(); $this->actingAs($staff);
        $data = ['title' => 'My story', 'content' => "My story\nCHAPTER 1 - First\nChapter body", 'status' => 'published', 'duplicates' => 'skip'];
        $this->post('/admin/import/save', $data)->assertRedirect();
        $this->assertDatabaseHas('posts', ['created_by' => $staff->id, 'chapter_number' => 1]);
        $chapter = Post::first(); $this->get(post_url($chapter->load('series')))->assertOk();
        $this->actingAs($this->staff())->post('/admin/import/save', $data + ['series_id' => $chapter->series_id])->assertForbidden();
    }

    public function test_post_and_series_filters(): void
    {
        $admin = User::factory()->create(['role_id' => Role::factory()->create(['name' => 'Super Admin'])->id]);
        $category = \App\Models\Category::factory()->create();
        $series = \App\Models\Series::factory()->create(['created_by' => $admin->id, 'status' => 'published']);
        $series->categories()->attach($category);
        $post = Post::factory()->create(['title' => 'Matching chapter', 'type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 1, 'created_by' => $admin->id, 'status' => 'bin']);
        $post->categories()->attach($category);
        Post::factory()->create(['title' => 'Excluded article', 'status' => 'draft']);
        $this->actingAs($admin)->get('/admin/posts?'.http_build_query(['status' => 'bin', 'category_id' => $category->id, 'series_id' => $series->id, 'created_by' => $admin->id]))->assertOk()->assertSee('Matching chapter')->assertDontSee('Excluded article');
        $this->get('/admin/series?'.http_build_query(['status' => 'published', 'category_id' => $category->id, 'created_by' => $admin->id]))->assertOk()->assertSee($series->title);
        $this->get('/admin/series?status=draft')->assertOk()->assertDontSee($series->title);
    }

    public function test_duplicate_resource_slugs_get_numbered_suffixes_and_keep_their_slug_on_update(): void
    {
        $admin = User::factory()->create(['role_id' => Role::factory()->create(['name' => 'Super Admin'])->id]);
        $this->actingAs($admin);
        foreach (['series' => \App\Models\Series::class, 'categories' => \App\Models\Category::class, 'tags' => \App\Models\Tag::class] as $resource => $model) {
            $data = ['title' => 'Same title', 'name' => 'Same name', 'slug' => 'same-link', 'content' => '<p>Page body</p>', 'status' => 'draft'];
            foreach (['same-link', 'same-link-2', 'same-link-3'] as $slug) {
                $this->post('/admin/'.$resource, $data)->assertRedirect()->assertSessionHasNoErrors();
                $this->assertDatabaseHas($resource, ['slug' => $slug]);
            }
            $record = $model::where('slug', 'same-link-2')->firstOrFail();
            $this->put('/admin/'.$resource.'/'.$record->id, array_replace($data, ['slug' => $record->slug]))->assertRedirect()->assertSessionHasNoErrors();
            $this->assertDatabaseHas($resource, ['id' => $record->id, 'slug' => 'same-link-2']);
        }
    }
}
