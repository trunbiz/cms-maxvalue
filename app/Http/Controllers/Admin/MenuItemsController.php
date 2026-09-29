<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuItemsRequest;
use App\Models\Menu;
use App\Services\MenuService;

class MenuItemsController extends Controller
{
    public function __invoke(MenuItemsRequest $request, int $menu, MenuService $service)
    {
        $service->save(Menu::select(['id', 'name', 'slug'])->findOrFail($menu), $request->validated('items'));

        return response()->json(['message' => 'Đã lưu menu.']);
    }
}
