<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Series;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SiteService
{
    public function settings(): array
    {
        return Cache::rememberForever('site.settings', fn () => Setting::pluck('value', 'key')->all());
    }

    public function categories()
    {
        return Cache::rememberForever('site.categories', fn () => Category::select(['id', 'name', 'slug', 'description', 'seo_title', 'seo_keywords', 'seo_description'])->orderBy('name')->get());
    }

    public function menus(): array
    {
        return Cache::rememberForever('site.menus', function () {
            $menus = Menu::select(['id', 'name', 'slug'])->with('items')->get();
            $targets = ['page' => Page::pluck('slug', 'id'), 'category' => Category::pluck('slug', 'id'), 'series' => Series::published()->pluck('slug', 'id')];
            $prefix = ['page' => 'trang', 'category' => 'danh-muc', 'series' => 'truyen'];
            $result = [];
            foreach ($menus as $menu) {
                $items = [];
                foreach ($menu->items as $item) {
                    $href = $item->type === 'url' ? $item->url : (isset($targets[$item->type][$item->target_id]) ? '/'.$prefix[$item->type].'/'.$targets[$item->type][$item->target_id] : null);
                    if ($href) {
                        $items[] = ['id' => $item->id, 'parent_id' => $item->parent_id, 'label' => $item->label, 'href' => $href];
                    }
                } $result[$menu->slug] = $this->tree($items);
            }

            return $result;
        });
    }

    private function tree(array $items, ?int $parent = null): array
    {
        $tree = [];
        foreach ($items as $item) {
            if ($item['parent_id'] === $parent) {
                $item['children'] = $this->tree($items, $item['id']);
                $tree[] = $item;
            }
        }

        return $tree;
    }
}
