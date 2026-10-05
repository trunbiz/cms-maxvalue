<?php

namespace App\Services;

use App\Models\Page;
use App\Models\Post;
use App\Models\PostContent;
use App\Models\Series;
use App\Models\Setting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class MediaService
{
    public function upload(UploadedFile $file, string $folder): string
    {
        if (! preg_match('/^[a-z0-9_-]+$/i', $folder)) {
            throw new \InvalidArgumentException('Invalid folder.');
        }
        $size = getimagesize($file->getRealPath());
        if (! $size || $size[0] * $size[1] > 40000000) {
            throw \Illuminate\Validation\ValidationException::withMessages(['image' => 'Images must not exceed 40 million pixels.']);
        }
        $image = (new ImageManager(new Driver))->read($file->getRealPath())->scaleDown(width: 1200)->toWebp(82);
        $path = $folder.'/'.now()->format('Y/m').'/'.Str::uuid().'.webp';
        if (! Storage::disk(config('cloudflare.media_disk'))->put($path, (string) $image)) {
            throw new \RuntimeException('Unable to save the image.');
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if (! $path || str_contains($path, '..') || str_contains($path, '://')) {
            return;
        }
        if (Post::where('image', $path)->exists() || Series::where('image', $path)->exists() || Setting::where('value', $path)->exists() || PostContent::where('content', 'like', '%'.$path.'%')->exists() || Page::where('content', 'like', '%'.$path.'%')->exists()) {
            return;
        }
        Storage::disk(config('cloudflare.media_disk'))->delete($path);
    }

    public function url(?string $path): string
    {
        if (in_array($path, ['images/logo/logo.png', 'images/logo/logo.ico'], true)) return asset($path);
        if (! $path) {
            return asset('images/book-placeholder.svg');
        }
        if (! preg_match('~^[a-zA-Z0-9_/-]+(?:\.[a-zA-Z0-9]+)?$~', $path) || str_contains($path, '..') || str_starts_with($path, '/')) {
            return asset('images/book-placeholder.svg');
        }
        if (config('cloudflare.media_disk') === 'r2' && config('cloudflare.r2_url')) {
            return rtrim(config('cloudflare.r2_url'), '/').'/'.ltrim($path, '/');
        }

        return Storage::disk(config('cloudflare.media_disk'))->url($path);
    }
}
