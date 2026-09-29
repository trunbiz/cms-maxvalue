<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Models\Setting;
use App\Services\CacheInvalidator;
use App\Services\MediaService;
use App\Services\SiteService;

class SettingsController extends Controller
{
    public function edit(SiteService $site)
    {
        return view('admin.settings', ['settings' => $site->settings()]);
    }

    public function update(SettingsRequest $request, MediaService $media, CacheInvalidator $cache)
    {
        foreach ($request->validated() as $key => $value) {
            $old = Setting::where('key', $key)->value('value');
            if (in_array($key, ['logo', 'favicon'])) {
                $value = $media->upload($value, 'branding');
            }
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
            if (in_array($key, ['logo', 'favicon'])) {
                $media->delete($old);
            }
        }
        $cache->invalidate();

        return back()->with('success', 'Đã lưu cài đặt.');
    }
}
