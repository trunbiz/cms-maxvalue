<?php

namespace App\Services;

class ContentService
{
    public function clean(string $html): string
    {
        $config = \HTMLPurifier_Config::createDefault();
        $config->set('Cache.SerializerPath', storage_path('framework/cache'));
        $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,s,h2,h3,h4,blockquote,ul,ol,li,a[href|title],img[src|alt|width|height],table,thead,tbody,tr,th,td,span');
        $html = (new \HTMLPurifier($config))->purify($html);
        $base = substr(media_url('editor'), 0, -strlen('editor'));
        $localBase = parse_url($base, PHP_URL_PATH) ?: '/';

        return preg_replace_callback('~<img\b[^>]*>~i', function ($match) use ($base, $localBase) {
            if (! preg_match('~\bsrc="([^"]+)"~', $match[0], $source)) {
                return '';
            }
            $src = html_entity_decode($source[1], ENT_QUOTES, 'UTF-8');
            if (str_starts_with($src, $base)) {
                $path = substr($src, strlen($base));
            } elseif (str_starts_with($src, '/media/')) {
                $path = substr($src, 7);
            } elseif ($localBase !== '/' && str_starts_with($src, $localBase)) {
                $path = substr($src, strlen($localBase));
            } else {
                return '';
            }
            if (! preg_match('~^[a-zA-Z0-9_/-]+\.(webp|png|jpe?g|gif)$~', $path) || str_contains($path, '..')) {
                return '';
            }

            return str_replace($source[0], 'src="/media/'.e($path).'"', $match[0]);
        }, $html);
    }

    public function render(string $html): string
    {
        return preg_replace_callback('~src=["\']/media/([^"\']+)["\']~', fn ($m) => 'loading="lazy" src="'.e(media_url($m[1])).'"', $html);
    }
}
