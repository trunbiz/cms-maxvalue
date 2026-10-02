<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

class PostSlugService
{
    public function makeMany(array $titles, User $author): array
    {
        $name = substr(Str::slug($author->name) ?: $author->username, 0, 80);
        $candidates = array_map(fn ($title) => rtrim(substr(Str::slug($title) ?: 'post', 0, 155), '-').'/'.$name, $titles);
        $used = [];
        foreach (array_chunk(array_values($candidates), 200) as $chunk) {
            foreach (Post::whereIn('slug', $chunk)->pluck('slug') as $slug) $used[$slug] = true;
        }
        foreach ($candidates as $key => $slug) {
            if (isset($used[$slug])) $slug = $this->make($titles[$key], $author);
            while (isset($used[$slug])) {
                $slug = explode('/', $candidates[$key])[0].'-'.random_int(100, 999999).'/'.$name;
            }
            $candidates[$key] = $slug;
            $used[$slug] = true;
        }
        return $candidates;
    }

    public function make(string $title, User $author, ?int $id = null): string
    {
        $name = substr(Str::slug($author->name) ?: $author->username, 0, 80);
        $base = rtrim(substr(Str::slug(explode('/', $title)[0]) ?: 'post', 0, 155), '-');
        $slug = $base.'/'.$name;
        while (Post::where('slug', $slug)->when($id, fn ($q) => $q->where('id', '!=', $id))->exists()) {
            $slug = $base.'-'.random_int(100, 999999).'/'.$name;
        }

        return $slug;
    }
}
