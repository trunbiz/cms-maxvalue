<?php
namespace Tests\Feature;
use App\Models\Post;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\CacheInvalidator;
use App\Services\SiteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class OnePublishTest extends TestCase
{
    use RefreshDatabase;
    private function staff(bool $admin = false): User
    {
        return User::factory()->create(['role_id' => Role::firstOrCreate(['name' => $admin ? 'Super Admin' : 'Employee'], ['modules' => ['posts', 'settings']])->id]);
    }

    public function test_preset_settings_keep_the_saved_custom_pattern_for_later_edits(): void
    {
        $this->actingAs($this->staff(true));
        $custom = '/read/%year%/%postname%/';
        $this->put('/admin/settings', ['permalink_structure' => 'custom', 'permalink_custom' => $custom])->assertSessionHasNoErrors();
        $this->put('/admin/settings', ['permalink_structure' => 'day'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('settings', ['key' => 'permalink_custom', 'value' => $custom]);
        $this->get('/admin/settings')->assertOk()->assertSee('data-permalink-settings', false)
            ->assertSee('value="/%year%/%monthnum%/%day%/%postname%/"', false)
            ->assertSee('data-custom-structure="'.$custom.'"', false)->assertSee('Example URL');
    }
    public function test_brand_navigation_and_metadata_are_fixed(): void
    {
        Setting::create(['key'=>'site_name', 'value'=>'Old brand']);
        Setting::create(['key'=>'logo', 'value'=>'old.png']);
        $this->get('/')->assertOk()->assertSee('<title>Latest Stories and Reading</title>', false)
            ->assertSee(config('publication.description'))->assertSee('OnePublish')->assertDontSee('Old brand')
            ->assertSee('/images/logo/logo.png', false)->assertSee('/images/logo/logo.ico', false)->assertDontSee('reading-progress');
        foreach (['/stories', '/liferature', '/articles'] as $path) $this->get($path)->assertOk();
        foreach (array_keys(\App\Services\PublisherService::PAGES) as $slug) $this->get('/pages/'.$slug)->assertOk();
        $this->actingAs($this->staff(true))->get('/admin/settings')->assertOk()->assertDontSee('name="site_name"', false)->assertDontSee('name="logo"', false);
        foreach (['pages', 'menus'] as $resource) {
            $this->get('/admin/'.$resource)->assertNotFound();
            $this->post('/admin/'.$resource, [])->assertNotFound();
        }
    }
    public function test_bulk_actions_are_atomic_and_enforce_post_ownership(): void
    {
        $staff = $this->staff();
        $own = Post::factory()->create(['created_by'=>$staff->id, 'status'=>'draft', 'published_at'=>null]);
        $other = Post::factory()->create(['created_by'=>$this->staff()->id]);
        $this->actingAs($staff)->get('/admin/posts')->assertOk()->assertSee('data-select-all', false)->assertSee('data-select-post', false);
        $this->post('/admin/posts/bulk', ['action'=>'bin', 'ids'=>[$own->id, $other->id]])->assertForbidden();
        $this->assertSame('draft', $own->fresh()->status);
        foreach (['published', 'draft', 'bin'] as $action) {
            $this->post('/admin/posts/bulk', ['action'=>$action, 'ids'=>[$own->id]])->assertRedirect();
            $this->assertSame($action, $own->fresh()->status);
        }
        $this->assertNotNull($own->fresh()->published_at);
        $this->postJson('/admin/posts/bulk', ['action'=>'delete', 'ids'=>[]])->assertUnprocessable();
    }
    public function test_all_permalink_modes_resolve_and_hide_drafts(): void
    {
        $post = Post::factory()->create(['slug'=>'sample/author', 'published_at'=>'2026-09-01 00:00:00']);
        $admin = $this->staff(true);
        foreach (['plain', 'day', 'month', 'numeric', 'name', 'custom'] as $mode) {
            $this->actingAs($admin)->put('/admin/settings', ['permalink_structure'=>$mode, 'permalink_custom'=>'/read/%year%/%post_id%/%postname%/'])->assertSessionHasNoErrors();
            $url = post_url($post);
            $this->get($url)->assertOk()->assertSee($post->title);
            $this->assertStringContainsString(htmlspecialchars($url, ENT_QUOTES), $this->get('/sitemap.xml')->streamedContent());
            $post->update(['status'=>'draft']);
            app(CacheInvalidator::class)->invalidate();
            $this->get($url)->assertNotFound();
            $post->update(['status'=>'published']);
        }
        $this->get('/2026/10/invalid')->assertNotFound();
        $this->put('/admin/settings', ['permalink_structure'=>'custom', 'permalink_custom'=>'/static/'])->assertSessionHasErrors('permalink_custom');
        $this->put('/admin/settings', ['permalink_structure'=>'custom', 'permalink_custom'=>'/admin/%post_id%/'])->assertSessionHasErrors('permalink_custom');
    }
}
