<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Series;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function assertMeta($response, string $attribute, string $name, string $value): void
    {
        $response->assertSee('<meta '.$attribute.'="'.$name.'" content="'.e($value).'">', false);
    }

    public function test_article_has_public_server_rendered_social_metadata_and_featured_image(): void
    {
        config(['cloudflare.media_disk' => 'r2', 'cloudflare.r2_url' => 'https://media.example.org']);
        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()->subDay(),
            'title' => 'Original title', 'seo_title' => 'Share "title" & more',
            'seo_description' => '<p>A useful summary &amp; details.</p>', 'image' => 'posts/cover.webp']);
        $response = $this->withHeaders(['User-Agent' => 'facebookexternalhit/1.1'])->get(post_url($post))->assertOk();
        $this->assertMeta($response, 'property', 'og:title', $post->seo_title);
        $this->assertMeta($response, 'property', 'og:description', 'A useful summary & details.');
        $this->assertMeta($response, 'property', 'og:image', 'https://media.example.org/posts/cover.webp');
        $this->assertMeta($response, 'property', 'og:image:secure_url', 'https://media.example.org/posts/cover.webp');
        $this->assertMeta($response, 'property', 'og:image:type', 'image/webp');
        $this->assertMeta($response, 'property', 'og:url', post_url($post));
        $this->assertMeta($response, 'name', 'twitter:title', $post->seo_title);
        $this->assertMeta($response, 'name', 'twitter:image', 'https://media.example.org/posts/cover.webp');
        $this->assertEmpty($response->headers->getCookies());
    }

    public function test_chapter_uses_series_cover_and_description_when_its_own_fields_are_empty(): void
    {
        $series = Series::factory()->create(['status' => 'published', 'image' => 'series/cover.webp',
            'description' => 'About this Series', 'seo_description' => 'Series share description']);
        $chapter = Post::factory()->create(['type' => 'chapter', 'series_id' => $series->id,
            'chapter_number' => 1, 'status' => 'published', 'published_at' => now()->subDay(),
            'image' => null, 'excerpt' => null, 'seo_description' => null, 'seo_title' => null]);
        $response = $this->get(post_url($chapter))->assertOk();
        $this->assertMeta($response, 'property', 'og:title', $chapter->title);
        $this->assertMeta($response, 'property', 'og:image', url(media_url($series->image)));
        $this->assertMeta($response, 'property', 'og:description', 'Series share description');
        $chapter->update(['image' => 'posts/chapter.webp', 'seo_description' => 'Chapter summary']);
        $response = $this->get(post_url($chapter))->assertOk();
        $this->assertMeta($response, 'property', 'og:image', url(media_url('posts/chapter.webp')));
        $this->assertMeta($response, 'property', 'og:description', 'Chapter summary');
    }

    public function test_empty_seo_fields_use_the_current_title_and_description(): void
    {
        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()->subDay(),
            'title' => 'Article title', 'excerpt' => 'Article description', 'seo_title' => '', 'seo_description' => '']);
        $response = $this->get(post_url($post))->assertOk();
        $this->assertMeta($response, 'property', 'og:title', 'Article title');
        $this->assertMeta($response, 'name', 'description', 'Article description');
        $post->update(['title' => 'Changed title', 'excerpt' => 'Changed description']);
        $response = $this->get(post_url($post))->assertOk();
        $this->assertMeta($response, 'property', 'og:title', 'Changed title');
        $this->assertMeta($response, 'property', 'og:description', 'Changed description');
        $series = Series::factory()->create(['status' => 'published', 'title' => 'Series title',
            'description' => 'Series description', 'seo_title' => null, 'seo_description' => null]);
        $response = $this->get('/stories/'.$series->slug)->assertOk();
        $this->assertMeta($response, 'property', 'og:title', 'Series title');
        $this->assertMeta($response, 'name', 'description', 'Series description');
    }

    public function test_missing_featured_image_uses_a_jpeg_fallback_and_content_summary(): void
    {
        $post = Post::factory()->create(['type' => 'normal', 'status' => 'published', 'published_at' => now()->subDay(),
            'image' => null, 'excerpt' => '', 'seo_description' => null]);
        $post->content()->create(['content' => '<p>First paragraph.</p><p>Second paragraph.</p>']);
        $response = $this->get(post_url($post))->assertOk();
        $this->assertMeta($response, 'property', 'og:image', asset('images/social-default.jpg'));
        $this->assertMeta($response, 'property', 'og:image:type', 'image/jpeg');
        $this->assertMeta($response, 'property', 'og:description', 'First paragraph. Second paragraph.');
        $this->assertFileExists(public_path('images/social-default.jpg'));
        Setting::create(['key' => 'logo', 'value' => 'settings/logo.png']);
        app(\App\Services\CacheInvalidator::class)->invalidate();
        $response = $this->get(post_url($post))->assertOk();
        $this->assertMeta($response, 'property', 'og:image', url(media_url('settings/logo.png')));
    }
}
