<?php
namespace App\Services;
use App\Models\Post;
class PermalinkService
{
    public const PRESETS = ['plain' => '/?p=%post_id%', 'day' => '/%year%/%monthnum%/%day%/%postname%/', 'month' => '/%year%/%monthnum%/%postname%/', 'numeric' => '/archives/%post_id%/', 'name' => '/%postname%/', 'author' => '/%postname%/%author%/'];

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
        $parts = explode('/', $post->slug, 2);
        $author = $parts[1] ?? 'author-'.$post->created_by;
        $name = str_contains($structure, '%author%') ? $parts[0] : $post->slug;
        return strtr($structure, ['%year%'=>$date->format('Y'), '%monthnum%'=>$date->format('m'), '%day%'=>$date->format('d'), '%post_id%'=>(string)$post->id, '%postname%'=>$name, '%author%'=>$author]);
    }
    public function match(string $path): ?int
    {
        $structure = $this->structure();
        if ($structure === '') return null;
        $pattern = preg_quote(trim($structure, '/'), '~');
        $pattern = strtr($pattern, ['%year%'=>'[0-9]{4}', '%monthnum%'=>'[0-9]{2}', '%day%'=>'[0-9]{2}', '%post_id%'=>'(?P<id>[0-9]+)', '%postname%'=>'(?P<slug>[a-z0-9-]+(?:/[a-z0-9-]+)?)', '%author%'=>'(?P<author>[a-z0-9-]+)']);
        if (!preg_match('~^'.$pattern.'$~', trim($path, '/'), $matches)) return null;
        if (isset($matches['author'])) {
            $query = Post::select(['id', 'slug', 'created_by', 'published_at', 'created_at'])->where('type', 'normal');
            if (isset($matches['id'])) $query->whereKey($matches['id']);
            else $query->whereIn('slug', [$matches['slug'].'/'.$matches['author'], $matches['slug']]);
            $post = $query->first();
            return $post && trim($this->path($post), '/') === trim($path, '/') ? $post->id : null;
        }
        if (isset($matches['id'])) return (int)$matches['id'];
        return Post::where('slug', $matches['slug'])->where('type', 'normal')->value('id');
    }
}
