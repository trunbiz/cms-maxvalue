<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Page;
use App\Models\Post;
use App\Models\Role;
use App\Models\Series;
use App\Models\User;
use App\Services\ChapterImportService;
use App\Services\CloudflareService;
use App\Services\SiteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CmsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::factory()->create(['name' => 'Super Admin', 'modules' => array_keys(config('modules'))])->id]);
    }

    private function editor(array $modules = ['posts']): User
    {
        return User::factory()->create(['role_id' => Role::factory()->create(['modules' => $modules])->id]);
    }

    public function test_seed_and_public_pages_render_without_session_cookies(): void
    {
        $this->seed();
        Post::query()->update(['status' => 'published']);
        Series::query()->update(['status' => 'published']);
        Page::query()->update(['status' => 'published']);
        $this->assertDatabaseCount('posts', 155);
        $this->assertDatabaseCount('series', 5);
        foreach (['/', '/pages/about', '/categories/literature', '/tags/adventure', '/stories/where-the-wind-tells-stories', '/stories/where-the-wind-tells-stories/where-the-wind-tells-stories-chapter-1', '/articles/reading-slowly-to-know-yourself', '/search?q=ngay', '/robots.txt', '/sitemap.xml'] as $url) {
            $response = $this->get($url);
            $response->assertOk();
            $this->assertEmpty($response->headers->getCookies(), $url);
        }
    }

    public function test_admin_pages_and_forms_render(): void
    {
        $this->seed();
        $this->actingAs(User::select(['id', 'name', 'username', 'password', 'role_id'])->first());
        foreach (['dashboard', 'settings', 'users', 'roles', 'categories', 'tags', 'posts', 'series', 'pages', 'menus'] as $resource) {
            $this->get('/admin/'.$resource)->assertOk();
        }
        foreach (['users', 'roles', 'categories', 'tags', 'posts', 'series', 'pages', 'menus'] as $resource) {
            $this->get('/admin/'.$resource.'/create')->assertOk();
            $this->get('/admin/'.$resource.'/1/edit')->assertOk();
        }
    }

    public function test_login_permissions_and_no_public_registration(): void
    {
        $user = $this->editor();
        $this->get('/admin/posts')->assertRedirect('/admin/login');
        $this->post('/admin/login', ['username' => $user->username, 'password' => 'bad'])->assertSessionHasErrors('username');
        $this->post('/admin/login', ['username' => $user->username, 'password' => 'password'])->assertRedirect('/admin/posts');
        $this->assertAuthenticatedAs($user);
        $this->get('/admin/posts')->assertOk();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/register')->assertNotFound();
    }

    public function test_users_roles_and_privilege_guards(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $this->delete('/admin/users/'.$admin->id)->assertSessionHasErrors();
        $this->delete('/admin/roles/'.$admin->role_id)->assertSessionHasErrors();
        $user = $this->editor();
        $before = $user->password;
        $this->put('/admin/users/'.$user->id, ['name' => 'Tên mới', 'username' => $user->username, 'password' => '', 'role_id' => $user->role_id])->assertSessionHasNoErrors();
        $this->assertSame($before, $user->fresh()->password);
        $this->delete('/admin/roles/'.$user->role_id)->assertSessionHasErrors();
        $this->actingAs($this->editor(['users', 'roles']));
        $this->post('/admin/users', ['name' => 'X', 'username' => 'evil', 'password' => 'password', 'role_id' => $admin->role_id])->assertForbidden();
    }

    public function test_crud_slug_content_and_settings_permissions(): void
    {
        $this->actingAs($this->admin());
        $this->post('/admin/categories', ['name' => 'Tiếng Việt đẹp'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', ['slug' => 'tieng-viet-dep']);
        $this->post('/admin/posts', ['title' => 'Bài viết mới', 'type' => 'normal', 'status' => 'published', 'content' => '<p>Đẹp <strong>lắm</strong></p><script>alert(1)</script>', 'tags' => ['Thẻ mới']])->assertSessionHasNoErrors();
        $post = Post::with('content')->first();
        $this->assertStringNotContainsString('<script', $post->content->content);
        $this->assertDatabaseHas('tags', ['slug' => 'the-moi']);
        $this->get('/articles/'.$post->slug)->assertOk()->assertSee('Đẹp');
        $this->actingAs($this->editor(['settings']));
        $this->put('/admin/settings', ['site_name' => 'Tên mới', 'head_html' => '<script></script>'])->assertSessionHasErrors('head_html');
    }

    public function test_drafts_future_posts_and_wrong_series_are_hidden(): void
    {
        $series = Series::factory()->create();
        $other = Series::factory()->create();
        $chapter = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => 1]);
        $this->get('/stories/'.$other->slug.'/'.$chapter->slug)->assertNotFound();
        $series->update(['status' => 'draft']);
        $this->get('/stories/'.$series->slug.'/'.$chapter->slug)->assertNotFound();
        $post = Post::factory()->create(['published_at' => now()->addDay()]);
        $this->get('/articles/'.$post->slug)->assertNotFound();
    }

    public function test_import_html_plain_text_formats_and_bulk_storage(): void
    {
        $service = app(ChapterImportService::class);
        $preview = $service->preview("Tên truyện\nMô tả\nCHAPTER 1 - Khởi đầu\nĐoạn một\nchapter 2 – Tiếp nối\nĐoạn hai\nChapter 4: Kết\nĐoạn cuối");
        $this->assertSame('Tên truyện', $preview['title']);
        $this->assertCount(3, $preview['chapters']);
        $this->assertNotEmpty($preview['warnings']);
        $series = $service->import($preview, ['status' => 'published', 'duplicates' => 'skip', 'tags' => ['Phiêu lưu']]);
        $this->assertSame(3, $series->chapters()->count());
        $this->assertDatabaseCount('post_contents', 3);
        $this->assertDatabaseCount('post_tag', 3);
        $html = $service->preview('<p>Tên khác</p><p>Mô tả</p><h2>CHAPTER 1 - Đầu</h2><p><strong>Giữ đậm</strong></p><p>CHAPTER 2: Sau</p><p>Nội dung</p>');
        $this->assertStringContainsString('<strong>Giữ đậm</strong>', $html['chapters'][0]['content']);
        $soft = $service->preview('<p>Tên<br>Mô tả<br>CHAPTER 1 - A<br>Nội dung<br>CHAPTER 2 - B<br>Cuối</p>');
        $this->assertCount(2, $soft['chapters']);
    }

    public function test_existing_series_duplicates_skip_overwrite_and_atomic_import(): void
    {
        $service = app(ChapterImportService::class);
        $series = Series::factory()->create(['description' => 'Cũ']);
        $source = "Bỏ qua tiêu đề\nMô tả mới\nCHAPTER 1 - Đầu\nNội dung";
        $preview = $service->preview($source, $series->id);
        $service->import($preview, ['status' => 'published', 'duplicates' => 'skip']);
        $updated = $service->preview(str_replace('Đầu', 'Mới', $source), $series->id);
        $service->import($updated, ['status' => 'published', 'duplicates' => 'skip']);
        $this->assertDatabaseHas('posts', ['title' => 'Đầu']);
        $service->import($updated, ['status' => 'published', 'duplicates' => 'overwrite', 'update_description' => true]);
        $this->assertDatabaseHas('posts', ['title' => 'Mới']);
        $this->assertDatabaseCount('posts', 1);
        $this->assertStringContainsString('Mô tả mới', $series->fresh()->description);
        $bad = $service->preview("X\nCHAPTER 2 - Lỗi\nNội dung", $series->id);
        try {
            $service->import($bad, ['status' => 'published', 'category_id' => 99999]);
            $this->fail('Expected FK failure');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertDatabaseCount('posts', 1);
        }
    }

    public function test_import_300_chapters_and_repeated_numbers(): void
    {
        $text = "Truyện dài\nMô tả\n";
        for ($i = 1; $i <= 300; $i++) {
            $text .= "CHAPTER $i - Chương $i\nNội dung chương $i.\n";
        }
        $service = app(ChapterImportService::class);
        $preview = $service->preview($text);
        $series = $service->import($preview, ['status' => 'draft']);
        $this->assertSame(300, $series->chapters()->count());
        $this->assertDatabaseCount('post_contents', 300);
        $duplicate = $service->preview("X\nCHAPTER 301 - A\nMột\nCHAPTER 301 - B\nHai", $series->id);
        $this->assertNotEmpty($duplicate['warnings']);
        $service->import($duplicate, ['status' => 'draft', 'duplicates' => 'overwrite']);
        $this->assertDatabaseHas('posts', ['chapter_number' => 301, 'title' => 'B']);
    }

    public function test_preview_requires_confirmation_and_is_scoped_to_user(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $response = $this->post('/admin/import/preview', ['content' => "Truyện\nCHAPTER 1 - Đầu\nNội dung", 'status' => 'draft', 'duplicates' => 'skip']);
        $response->assertOk();
        $this->assertDatabaseCount('series', 0);
        $token = $response->viewData('token');
        $this->actingAs($this->editor())->post('/admin/import', ['token' => $token])->assertStatus(419);
        $this->actingAs($admin)->post('/admin/import', ['token' => $token])->assertRedirect();
        $this->assertDatabaseCount('posts', 1);
        $this->post('/admin/import', ['token' => $token])->assertStatus(419);
    }

    public function test_media_upload_resize_and_shared_image_deletion(): void
    {
        Storage::fake('public');
        config(['cloudflare.media_disk' => 'public']);
        $this->actingAs($this->admin());
        $response = $this->post('/admin/upload/editor', ['upload' => UploadedFile::fake()->image('photo.png', 1600, 1000)]);
        $response->assertOk()->assertJsonStructure(['url']);
        $files = Storage::disk('public')->allFiles('editor');
        $this->assertCount(1, $files);
        $path = $files[0];
        $this->assertStringEndsWith('.webp', $path);
        $size = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame(1200, $size[0]);
        $a = Post::factory()->create(['image' => $path]);
        $b = Post::factory()->create(['image' => $path]);
        $this->delete('/admin/posts/'.$a->id)->assertRedirect();
        Storage::disk('public')->assertExists($path);
        $this->delete('/admin/posts/'.$b->id)->assertRedirect();
        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas('posts', ['id' => $b->id, 'status' => 'bin']);
        $this->postJson('/admin/upload/editor', ['upload' => UploadedFile::fake()->create('bad.svg', 2, 'image/svg+xml')])->assertUnprocessable();
    }

    public function test_menu_nested_order_validation_and_cache_invalidation(): void
    {
        $this->actingAs($this->admin());
        $menu = Menu::factory()->create(['slug' => 'main']);
        $site = app(SiteService::class);
        $this->assertSame([], $site->menus()['main']);
        $items = [['id' => -1, 'parent_id' => null, 'label' => 'Cha', 'type' => 'url', 'url' => '/'], ['id' => -2, 'parent_id' => -1, 'label' => 'Con', 'type' => 'url', 'url' => '/search']];
        $this->putJson('/admin/menus/'.$menu->id.'/items', ['items' => $items])->assertOk();
        $this->assertSame('Con', $site->menus()['main'][0]['children'][0]['label']);
        $items[0]['parent_id'] = -2;
        $this->putJson('/admin/menus/'.$menu->id.'/items', ['items' => $items])->assertUnprocessable();
        $items[0]['url'] = 'javascript:alert(1)';
        $this->putJson('/admin/menus/'.$menu->id.'/items', ['items' => $items])->assertUnprocessable();
    }

    public function test_response_cache_is_shared_and_invalidated_on_update(): void
    {
        config(['responsecache.enabled' => true]);
        $page = Page::factory()->create(['title' => 'Nội dung cũ']);
        $guest = $this->get('/pages/'.$page->slug)->assertOk()->getContent();
        $admin = $this->admin();
        $this->actingAs($admin);
        $this->assertSame($guest, $this->get('/pages/'.$page->slug)->getContent());
        $this->put('/admin/pages/'.$page->id, ['title' => 'Nội dung mới', 'slug' => $page->slug, 'content' => '<p>Thay đổi</p>', 'status' => 'published'])->assertSessionHasNoErrors();
        $this->get('/pages/'.$page->slug)->assertSee('Nội dung mới')->assertDontSee('Nội dung cũ');
    }

    public function test_cloudflare_disabled_and_batches_of_thirty(): void
    {
        Http::fake(['*' => Http::response(['success' => true])]);
        $service = app(CloudflareService::class);
        $service->purgeUrls(['https://example.com']);
        Http::assertNothingSent();
        config(['cloudflare.purge_enabled' => true, 'cloudflare.zone_id' => 'test', 'cloudflare.api_token' => 'test']);
        $service->purgeUrls(array_map(fn ($i) => 'https://example.com/'.$i, range(1, 65)));
        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => count($request['files']) === 30);
    }

    public function test_editor_media_paths_are_relative_and_html_is_safe(): void
    {
        config(['cloudflare.media_disk' => 'public']);
        $path = 'editor/2026/09/test.webp';
        $clean = clean_html('<p>Hello<img src="'.media_url($path).'" onerror="alert(1)"></p><img src="https://external.example/image.png">');
        $this->assertStringContainsString('/media/'.$path, $clean);
        $this->assertStringNotContainsString('http', $clean);
        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringContainsString(media_url($path), content_html($clean));
        $this->assertStringContainsString('loading="lazy"', content_html($clean));
    }

    public function test_query_count_does_not_grow_per_chapter(): void
    {
        $this->seed();
        Post::query()->update(['status' => 'published']);
        Series::query()->update(['status' => 'published']);
        Cache::flush();
        DB::enableQueryLog();
        $this->get('/stories/where-the-wind-tells-stories')->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertLessThan(20, count($queries));
        foreach ($queries as $query) {
            $this->assertDoesNotMatchRegularExpression('/select\s+\*\s+from/i', $query['query']);
        }
    }

    public function test_bulk_delete_is_scoped_to_series(): void
    {
        $this->actingAs($this->admin());
        $a = Series::factory()->create();
        $b = Series::factory()->create();
        $post = Post::factory()->create(['type' => 'chapter', 'series_id' => $b->id, 'chapter_number' => 1]);
        $this->deleteJson('/admin/chapters/bulk-delete', ['series_id' => $a->id, 'ids' => [$post->id]])->assertUnprocessable();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
        $this->delete('/admin/chapters/bulk-delete', ['series_id' => $b->id, 'ids' => [$post->id]])->assertRedirect();
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'status' => 'bin']);
    }
}
