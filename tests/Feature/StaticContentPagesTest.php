<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticContentPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_information_pages_render_the_correct_content(): void
    {
        foreach (['about' => 'About us', 'contact' => 'Contact us', 'privacy-policy' => 'Privacy Policy', 'terms-of-use' => 'Terms of Use', 'dmca' => 'DMCA'] as $slug => $title) {
            $this->get('/pages/'.$slug)->assertOk()
                ->assertSee('<h1>'.$title.'</h1>', false)
                ->assertSee('reading-content article-content', false)
                ->assertDontSee('adserver.maxvaluead.com', false);
        }

        $this->get('/pages/contact')->assertSee('mailto:contact@mex.instazoomde.com', false)
            ->assertSee(url('/pages/dmca'), false);
        $this->get('/pages/about')->assertDontSee('<h1>Contact us</h1>', false);
    }

    public function test_dmca_is_discoverable_and_unknown_pages_remain_unavailable(): void
    {
        $this->get('/')->assertOk()->assertSee('href="/pages/dmca"', false);
        $this->assertStringContainsString(url('/pages/dmca'), $this->get('/sitemap.xml')->assertOk()->streamedContent());
        $this->get('/pages/unknown-policy')->assertNotFound();
    }
}
