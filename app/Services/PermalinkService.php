<?php
namespace App\Services;
use App\Models\Post;
class PermalinkService
{
    public const PRESETS = ['plain' => '/?p=%post_id%', 'day' => '/%year%/%monthnum%/%day%/%postname%/', 'month' => '/%year%/%monthnum%/%postname%/', 'numeric' => '/archives/%post_id%/', 'name' => '/%postname%/'];

    public function structure(): string
    {
        $settings = app(SiteService::class)->settings();
        $mode = $settings['permalink_structure'] ?? 'name';
        if ($mode === 'plain') return '';
        return $mode === 'custom' ? (($settings['permalink_custom'] ?? null) ?: self::PRESETS['name']) : (self::PRESETS[$mode] ?? self::PRESETS['name']);
    }
    public function path(Post $post): string
    {
        $structure = $this->structure();
        if ($structure === '') return '/?p='.$post->id;
        $date = $post->published_at ?? $post->created_at ?? now();
        return strtr($structure, ['%year%'=>$date->format('Y'), '%monthnum%'=>$date->format('m'), '%day%'=>$date->format('d'), '%post_id%'=>(string)$post->id, '%postname%'=>$post->slug]);
    }
    public function match(string $path): ?int
    {
        $structure = $this->structure();
        if ($structure === '') return null;
        $pattern = preg_quote(trim($structure, '/'), '~');
        $pattern = strtr($pattern, ['%year%'=>'[0-9]{4}', '%monthnum%'=>'[0-9]{2}', '%day%'=>'[0-9]{2}', '%post_id%'=>'(?P<id>[0-9]+)', '%postname%'=>'(?P<slug>[a-z0-9-]+(?:/[a-z0-9-]+)?)']);
        if (!preg_match('~^'.$pattern.'$~', trim($path, '/'), $matches)) return null;
        if (isset($matches['id'])) return (int)$matches['id'];
        return Post::where('slug', $matches['slug'])->where('type', 'normal')->value('id');
    }
}
