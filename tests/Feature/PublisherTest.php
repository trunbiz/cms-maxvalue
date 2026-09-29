<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Post;
use App\Models\Role;
use App\Models\User;
use App\Services\EnglishPublicationService;
use App\Services\PublisherService;
use Database\Seeders\PublicationPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublisherTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role_id' => Role::factory()->create(['name' => 'Super Admin', 'modules' => array_keys(config('modules'))])->id]);
    }

    private function profile(): array
    {
        return ['site_name' => 'Test Publication', 'publisher_name' => 'Test Publisher', 'contact_email' => 'contact@example.org', 'site_url' => 'https://publication.example.org'];
    }

    public function test_seed_content_and_unreviewed_policy_pages_stay_in_drafts(): void
    {
        $this->seed();
        $this->assertSame(0, Post::published()->count());
        $this->assertDatabaseCount('pages', 6);
        $this->assertSame(0, Page::published()->count());
        $this->get('/pages/privacy-policy')->assertNotFound();
        $this->get('/')->assertOk()->assertSee('lang="en"', false)->assertDontSee('[[publisher_name]]');
        $xml = $this->get('/sitemap.xml')->assertOk()->streamedContent();
        $this->assertStringNotContainsString('privacy-policy', $xml);
        $this->assertStringNotContainsString('where-the-wind', $xml);
    }

    public function test_profile_and_review_required_then_pages_and_footer_are_published(): void
    {
        $this->actingAs($this->admin());
        $this->seed(PublicationPagesSeeder::class);
        $this->post('/admin/settings/publish-pages', ['reviewed' => 1])->assertSessionHasErrors();
        $this->put('/admin/settings', $this->profile())->assertSessionHasNoErrors();
        $this->post('/admin/settings/publish-pages', [])->assertSessionHasErrors('reviewed');
        $this->post('/admin/settings/publish-pages', ['reviewed' => 1])->assertSessionHasNoErrors();
        $this->assertSame(6, Page::published()->count());
        $response = $this->get('/pages/privacy-policy')->assertOk()->assertSee('Test Publisher')->assertSee('contact@example.org')->assertDontSee('[[contact_email]]');
        $this->assertEmpty($response->headers->getCookies());
        $this->get('/')->assertSee('/pages/privacy-policy', false)->assertSee('/pages/contact', false);
        $this->assertStringContainsString('/pages/privacy-policy', $this->get('/sitemap.xml')->streamedContent());
    }

    public function test_page_cannot_publish_unresolved_identity_placeholders(): void
    {
        $this->actingAs($this->admin());
        $this->post('/admin/pages', ['title' => 'Privacy', 'status' => 'published', 'content' => '<p>Contact [[contact_email]]</p>'])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('pages', 0);
    }

    public function test_adsense_verification_requires_real_format_and_loads_no_ads(): void
    {
        $this->actingAs($this->admin());
        $this->get('/ads.txt')->assertNotFound();
        $this->put('/admin/settings', $this->profile() + ['adsense_publisher_id' => '<script>bad</script>'])->assertSessionHasErrors('adsense_publisher_id');
        $this->put('/admin/settings', $this->profile() + ['adsense_publisher_id' => 'ca-pub-1234567890123456', 'ads_txt' => 'other.example, 123, DIRECT'])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('<meta name="google-adsense-account" content="ca-pub-1234567890123456">', false)->assertDontSee('adsbygoogle.js');
        $this->get('/ads.txt')->assertOk()->assertSee('google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0', false)->assertSee('other.example');
    }

    public function test_article_hides_author_and_has_dates_schema_and_authenticated_draft_preview(): void
    {
        $post = Post::factory()->create(['status' => 'draft', 'author_name' => 'A Real Writer']);
        $post->content()->create(['content' => '<h2>A helpful heading</h2><p>Original article body.</p>']);
        $this->get('/articles/'.$post->slug)->assertNotFound();
        $this->get('/admin/posts/'.$post->id.'/preview')->assertRedirect('/admin/login');
        $this->actingAs($this->admin());
        $response = $this->get('/admin/posts/'.$post->id.'/preview')->assertOk()->assertSee('Private preview')->assertDontSee('A Real Writer')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $post->update(['status' => 'published']);
        $this->get('/articles/'.$post->slug)->assertOk()->assertDontSee('A Real Writer')->assertDontSee('"author":', false)->assertSee('Copy link')->assertSee('min read')->assertSee('"@type":"Article"', false)->assertSee('datePublished')->assertSee('dateModified');
    }

    public function test_legacy_urls_redirect_and_search_is_noindex(): void
    {
        $this->get('/bai-viet/example?ref=test')->assertStatus(301)->assertRedirect('/articles/example?ref=test');
        $this->get('/trang/gioi-thieu')->assertRedirect('/pages/about');
        $this->get('/truyen/story/chapter')->assertRedirect('/stories/story/chapter');
        $this->get('/search?q=reading')->assertOk()->assertSee('noindex,follow', false);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin', false)->assertSee('/sitemap.xml');
    }

    public function test_preparation_is_idempotent_and_preserves_authored_content(): void
    {
        $page = Page::factory()->create(['slug' => 'about', 'content' => '<p>My authored about page.</p>']);
        $post = Post::factory()->create(['title' => 'Đọc chậm để hiểu mình']);
        $post->content()->create(['content' => '<p>This is my own work, not the seeded body.</p>']);
        app(EnglishPublicationService::class)->prepare();
        app(EnglishPublicationService::class)->prepare();
        $this->assertDatabaseCount('pages', 6);
        $this->assertSame('<p>My authored about page.</p>', $page->fresh()->content);
        $this->assertSame('published', $post->fresh()->status);
        $this->assertSame('Đọc chậm để hiểu mình', $post->fresh()->title);
    }

    public function test_checklist_does_not_claim_empty_local_site_is_ready(): void
    {
        $checks = app(PublisherService::class)->checklist([]);
        $this->assertFalse($checks[0]['done']);
        $this->assertFalse($checks[1]['done']);
        $this->assertFalse(collect($checks)->firstWhere('label', 'Original published articles')['done']);
        $this->assertFalse(app(PublisherService::class)->publicUrl('http://localhost'));
    }
}
