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
}
