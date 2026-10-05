<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Models\Setting;
use App\Services\CacheInvalidator;
use App\Services\SiteService;
class SettingsController extends Controller
{
    public function edit(SiteService $site)
    {
        return view('admin.settings', ['settings' => $site->settings()]);
    }
    public function update(SettingsRequest $request, CacheInvalidator $cache)
    {
        foreach ($request->validated() as $key => $value) Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        $cache->invalidate();
        return back()->with('success', 'Settings saved.');
    }
}
