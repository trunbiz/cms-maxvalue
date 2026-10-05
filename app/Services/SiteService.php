<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SiteService
{
    public function settings(): array
    {
        return array_merge(Cache::rememberForever('site.settings', fn () => Setting::pluck('value', 'key')->all()), ['site_name' => config('publication.brand'), 'seo_title' => config('publication.title'), 'seo_description' => config('publication.description'), 'logo' => 'images/logo/logo.png', 'favicon' => 'images/logo/logo.ico', 'publisher_name' => config('publication.brand')]);
    }

    public function categories()
    {
        return Cache::rememberForever('site.categories', fn () => Category::select(['id', 'name', 'slug', 'description', 'seo_title', 'seo_keywords', 'seo_description'])->orderBy('name')->get());
    }

    public function menus(): array
    {
        $main = ['Home' => '/', 'Stories' => '/stories', 'Liferature' => '/liferature', 'Articles' => '/articles'];
        $footer = [];
        foreach (PublisherService::PAGES as $slug => $title) {
            if (in_array($slug, ['editorial-policy', 'copyright'], true)) {
                continue;
            }
            $footer[$title] = '/pages/'.$slug;
        }
        $items = fn ($links) => array_map(fn ($label, $href) => ['label' => $label, 'href' => $href, 'children' => []], array_keys($links), array_values($links));
        return ['main' => $items($main), 'footer' => $items($footer)];
    }

}
