<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Series;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MenuService
{
    public function save(Menu $menu, array $items): void
    {
        $ids = array_column($items, 'id');
        if (count($ids) !== count(array_unique($ids))) {
            $this->fail('Mục menu bị trùng.');
        }
        $owned = $menu->items()->pluck('id')->all();
        $map = [];
        foreach ($items as $item) {
            $id = $item['id'];
            if ($id > 0 && ! in_array($id, $owned, true)) {
                $this->fail('Mục menu không thuộc menu này.');
            }
            if ($item['type'] === 'url' && empty($item['url'])) {
                $this->fail('Vui lòng nhập URL.');
            }
            if ($item['type'] !== 'url') {
                $class = ['page' => Page::class, 'category' => Category::class, 'series' => Series::class][$item['type']];
                if (! $class::whereKey($item['target_id'])->exists()) {
                    $this->fail('Liên kết đích không tồn tại.');
                }
            }
            $map[$id] = $item['parent_id'] ?? null;
        }
        foreach ($map as $id => $parent) {
            $seen = [$id];
            while ($parent !== null) {
                if (! array_key_exists($parent, $map) || in_array($parent, $seen, true)) {
                    $this->fail('Cấp menu không hợp lệ hoặc tạo vòng lặp.');
                } $seen[] = $parent;
                if (count($seen) > 5) {
                    $this->fail('Menu tối đa 5 cấp.');
                } $parent = $map[$parent];
            }
        }
        DB::transaction(function () use ($menu, $items) {
            $real = [];
            foreach ($items as $i => $item) {
                $oldId = $item['id'];
                unset($item['id'],$item['parent_id']);
                $item['sort_order'] = $i;
                $row = $oldId > 0 ? $menu->items()->findOrFail($oldId) : new MenuItem(['menu_id' => $menu->id]);
                $row->fill($item);
                $row->parent_id = null;
                $row->save();
                $real[$oldId] = $row->id;
            }
            foreach ($items as $item) {
                MenuItem::whereKey($real[$item['id']])->update(['parent_id' => isset($item['parent_id']) ? $real[$item['parent_id']] : null]);
            }
            $menu->items()->whereNotIn('id', array_values($real))->delete();
        });
        app(CacheInvalidator::class)->invalidate();
    }

    private function fail(string $message): void
    {
        throw ValidationException::withMessages(['items' => $message]);
    }
}
