<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Post;

class PublisherService
{
    public const PAGES = [
        'about' => 'About',
        'contact' => 'Contact',
        'privacy-policy' => 'Privacy Policy',
        'terms-of-use' => 'Terms of Use',
        'dmca' => 'DMCA',
//        'editorial-policy' => 'Editorial Policy',
//        'copyright' => 'Copyright & Corrections',
    ];

    public function renderPage(string $html, array $settings): string
    {
        $values = [
            'site_name' => $settings['site_name'] ?? 'Reading Corner',
            'publisher_name' => $settings['publisher_name'] ?? '',
            'contact_email' => $settings['contact_email'] ?? '',
            'site_url' => $settings['site_url'] ?? config('app.url'),
        ];

        foreach ($values as $key => $value) {
            $html = str_replace('[['.$key.']]', e($value), $html);
        }

        return content_html($html);
    }

    public function profileComplete(array $settings): bool
    {
        return filled($settings['site_name'] ?? null)
            && filled($settings['publisher_name'] ?? null)
            && filter_var($settings['contact_email'] ?? '', FILTER_VALIDATE_EMAIL)
            && $this->publicUrl($settings['site_url'] ?? '');
    }

    public function publicUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return filter_var($url, FILTER_VALIDATE_URL)
            && parse_url($url, PHP_URL_SCHEME) === 'https'
            && $host && str_contains($host, '.')
            && ! filter_var($host, FILTER_VALIDATE_IP)
            && ! preg_match('/(?:^|\.)(?:localhost|test|local|invalid|example)$/i', $host);
    }

    public function checklist(array $settings): array
    {
        $pages = Page::select(['id', 'slug', 'title', 'status'])->whereIn('slug', array_keys(self::PAGES))->get()->keyBy('slug');
        $checks = [
            ['label' => 'Publisher identity and contact details', 'done' => $this->profileComplete($settings), 'detail' => 'Enter the real publisher name, contact email and public HTTPS website address.'],
            ['label' => 'Public production URL', 'done' => $this->publicUrl(config('app.url')) && rtrim(config('app.url'), '/') === rtrim($settings['site_url'] ?? '', '/'), 'detail' => 'Deploy the website and set APP_URL to the same public HTTPS domain. Localhost cannot be submitted.'],
            ['label' => 'AdSense ownership verification', 'done' => (bool) preg_match('/^ca-pub-\d{16}$/', $settings['adsense_publisher_id'] ?? ''), 'detail' => 'Enter your own Publisher ID to output the verification meta tag and ads.txt entry. This does not request approval or start ads.'],
        ];
        foreach (self::PAGES as $slug => $title) {
            $page = $pages->get($slug);
            $checks[] = ['label' => $title, 'done' => $page?->status === 'published', 'detail' => 'Review the text, confirm it describes your actual practices, then publish.', 'edit_url' => $page ? '/admin/pages/'.$page->id.'/edit' : '/admin/pages/create'];
        }
        $checks[] = ['label' => 'Original published articles', 'done' => Post::published()->where('type', 'normal')->where('is_demo', false)->exists(), 'detail' => 'Publish useful original work that you own or may use. This is a technical presence check, not an AdSense content-quality assessment or a minimum article count.'];
        $checks[] = ['label' => 'Demo content kept unpublished', 'done' => ! Post::published()->where('is_demo', true)->exists(), 'detail' => 'Repeated sample chapters and short demo articles are drafts. Replace them with your own substantial content.'];
        $checks[] = ['label' => 'Consent and advertising setup reviewed', 'done' => ($settings['consent_reviewed'] ?? '0') === '1', 'detail' => 'Before serving ads, configure the applicable consent messages in AdSense Privacy & messaging or a Google-certified CMP. A basic cookie banner is not a substitute.'];

        return $checks;
    }

    public function adsText(array $settings): string
    {
        $text = trim($settings['ads_txt'] ?? '');
        $id = $settings['adsense_publisher_id'] ?? '';
        if (preg_match('/^ca-pub-\d{16}$/', $id)) {
            $line = 'google.com, '.substr($id, 3).', DIRECT, f08c47fec0942fa0';
            if (! in_array($line, preg_split('/\R/', $text), true)) {
                $text = trim($text."\n".$line);
            }
        }

        return $text !== '' ? $text."\n" : '';
    }
}
