<?php

namespace App\Services;

use App\Models\Series;

class SocialPreviewService
{
    public function metadata($entity, array $settings, ?Series $series = null, string $content = ''): array
    {
        $description = $entity?->seo_description ?: ($entity?->excerpt ?: ($entity?->description ?: null));
        $description = $description ?: ($series?->seo_description ?: $series?->description);
        $description = $description ?: ($content ?: ($settings['seo_description'] ?? ''));
        $description = html_entity_decode(strip_tags(preg_replace('~</(?:p|div|h[1-6])>|<br\s*/?>~i', ' ', $description)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = trim(preg_replace('/\s+/u', ' ', $description));
        $path = $entity?->image ?: ($series?->image ?: ($settings['logo'] ?? null));
        $image = $path ? media_url($path) : asset('images/social-default.jpg');
        $image = url($image);
        $extension = strtolower(pathinfo(parse_url($image, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        $types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'gif' => 'image/gif'];

        return ['description' => mb_substr($description, 0, 200), 'image' => $image,
            'image_alt' => $entity?->title ?? ($settings['site_name'] ?? 'Reading Corner'),
            'image_type' => $types[$extension] ?? null, 'default_image' => ! $path];
    }
}
