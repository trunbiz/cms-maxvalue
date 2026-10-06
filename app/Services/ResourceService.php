<?php

namespace App\Services;

use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResourceService
{
    public function withDefaultCategory(array $data): array
    {
        if (empty($data['category_ids']) && empty($data['category_id'])) {
            $category = \App\Models\Category::firstOrCreate(['slug' => 'stories'], ['name' => 'Stories']);
            $data['category_id'] = $category->id;
            $data['category_ids'] = [$category->id];
        }

        return $data;
    }

    public function save(string $resource, $record, array $data, $actor)
    {
        if ($resource === 'posts' && !$record->exists) $data = $this->withDefaultCategory($data);
        if ($resource === 'users') {
            $targetRole = \App\Models\Role::select(['id', 'name', 'modules'])->findOrFail($data['role_id']);
            if (! $actor->isSuperAdmin() && ($targetRole->name === 'Super Admin' || ($record->exists && $record->isSuperAdmin()))) {
                abort(403);
            }
            if (! $actor->isSuperAdmin()) {
                foreach ($targetRole->modules as $module) {
                    abort_unless($actor->hasModule($module), 403);
                }
            }
            if (! $actor->isSuperAdmin() && $record->exists) {
                foreach ($record->role->modules as $module) {
                    abort_unless($actor->hasModule($module), 403);
                }
            }
            if ($record->exists && $record->isSuperAdmin() && $targetRole->name !== 'Super Admin' && User::where('role_id', $record->role_id)->count() <= 1) {
                throw ValidationException::withMessages(['role_id' => 'At least one Super Admin must remain.']);
            }
            if (empty($data['password'])) {
                unset($data['password']);
            }
        }
        if ($resource === 'roles' && ! $actor->isSuperAdmin()) {
            if ($record->name === 'Super Admin' || ($data['name'] ?? '') === 'Super Admin') {
                abort(403);
            }
            foreach (array_merge($data['modules'] ?? [], $record->modules ?? []) as $module) {
                abort_unless($actor->hasModule($module), 403);
            }
        }
        if ($resource === 'posts' && $record->exists) {
            abort_unless($actor->managesAllPosts() || $record->created_by === $actor->id, 403);
        }
        if (in_array($resource, ['posts', 'series']) && !$record->exists) $data['created_by'] = $actor->id;
        if ($resource === 'posts') {
            $author = $record->created_by ? User::findOrFail($record->created_by) : $actor;
            $data['slug'] = app(PostSlugService::class)->make($data['slug'] ?: $data['title'], $author, $record->id);
        } elseif (isset($data['slug'])) {
            $data['slug'] = app(SlugService::class)->unique(get_class($record), $data['slug'], $record->id);
        }
        $uploadedPath = $data['image_path'] ?? null;
        unset($data['image_path']);
        if ($uploadedPath) {
            abort_unless(in_array($uploadedPath, session('featured_uploads', []), true), 422);
        }
        $old = $record->image;
        $new = null;
        if (isset($data['image'])) {
            $new = app(MediaService::class)->upload($data['image'], $resource);
            $data['image'] = $new;
        } elseif ($uploadedPath) {
            $data['image'] = $uploadedPath;
        } else {
            unset($data['image']);
        }
        try {
            DB::transaction(function () use ($resource, $record, $data) {
                $content = $data['content'] ?? null;
                $tags = $data['tags'] ?? ($resource === 'posts' && $record->exists ? null : []);
                $categoryIds = $data['category_ids'] ?? array_filter([$data['category_id'] ?? null]);
                unset($data['tags'], $data['category_ids']);
                if ($resource === 'posts') {
                    if ($data['type'] === 'chapter' && $data['status'] === 'published' && ! empty($data['publish_series'])) {
                        Series::whereKey($data['series_id'])->update(['status' => 'published', 'updated_at' => now()]);
                    }
                    unset($data['publish_series']);
                    unset($data['content']);
                    if ($record->exists && $record->is_demo && clean_html($content) !== \App\Models\PostContent::where('post_id', $record->id)->value('content')) {
                        $data['is_demo'] = false;
                    }
                    if ($data['type'] === 'normal') {
                        $data['series_id'] = null;
                        $data['chapter_number'] = null;
                    }
                    if ($data['status'] === 'published' && empty($data['published_at'])) {
                        $data['published_at'] = now();
                    }
                }
                if ($resource === 'pages') {
                    $data['content'] = clean_html($content);
                }
                if ($resource === 'roles') {
                    $data['modules'] = $data['modules'] ?? [];
                }
                $record->fill($data)->save();
                if ($resource === 'posts') {
                    $record->content()->updateOrCreate(['post_id' => $record->id], ['content' => clean_html($content)]);
                    if ($record->series_id) {
                        Series::whereKey($record->series_id)->update(['updated_at' => now()]);
                    }
                }
                if (in_array($resource, ['posts', 'series'])) {
                    if ($tags !== null) $this->syncTags($record, $this->tagIds($tags));
                    $this->syncCategories($record, $categoryIds);
                }
            });
        } catch (\Throwable $e) {
            if ($new) {
                app(MediaService::class)->delete($new);
            }
            throw $e;
        }
        if ($old && $old !== $record->image) {
            app(MediaService::class)->delete($old);
        }
        app(CacheInvalidator::class)->invalidate();

        return $record;
    }

    public function tagIds(array $tags): array
    {
        $requestedIds = [];
        $names = [];
        foreach ($tags as $name) {
            if (ctype_digit((string) $name)) {
                $requestedIds[] = (int) $name;
            } else {
                $name = Str::startsWith($name, 'new:') ? substr($name, 4) : $name;
                $slug = Str::slug($name);
                if ($slug !== '') {
                    $names[$slug] = $name;
                }
            }
        }
        $ids = $requestedIds ? Tag::whereIn('id', $requestedIds)->pluck('id')->all() : [];
        if ($names) {
            $rows = [];
            foreach ($names as $slug => $name) {
                $rows[] = ['name' => $name, 'slug' => $slug, 'created_at' => now(), 'updated_at' => now()];
            }
            DB::table('tags')->insertOrIgnore($rows);
            $ids = array_merge($ids, Tag::whereIn('slug', array_keys($names))->pluck('id')->all());
        }

        return array_values(array_unique($ids));
    }

    public function syncTags($record, array $ids): void
    {
        $entity = $record instanceof Series ? 'series' : 'post';
        DB::table($entity.'_tag')->where($entity.'_id', $record->id)->delete();
        if ($ids) {
            DB::table($entity.'_tag')->insert(array_map(fn ($id) => [$entity.'_id' => $record->id, 'tag_id' => $id], $ids));
        }
    }

    public function syncCategories($record, array $ids): void
    {
        $entity = $record instanceof Series ? 'series' : 'post';
        DB::table('category_'.$entity)->where($entity.'_id', $record->id)->delete();
        if ($ids) {
            DB::table('category_'.$entity)->insert(array_map(fn ($id) => [$entity.'_id' => $record->id, 'category_id' => $id], array_values(array_unique($ids))));
        }
    }

    public function delete(string $resource, $record, $actor): void
    {
        if ($resource === 'users') {
            if ($record->id === $actor->id) {
                throw ValidationException::withMessages(['user' => 'You cannot delete your own account.']);
            }
            if ($record->isSuperAdmin() && (! $actor->isSuperAdmin() || User::where('role_id', $record->role_id)->count() <= 1)) {
                abort(403);
            }
        }
        if ($resource === 'roles' && ! $actor->isSuperAdmin() && $record->name === 'Super Admin') {
            abort(403);
        }
        if ($resource === 'posts') {
            abort_unless($actor->managesAllPosts() || $record->created_by === $actor->id, 403);
            $record->update(['status' => 'bin']);
            app(CacheInvalidator::class)->invalidate();
            return;
        }
        $paths = [$record->image];
        if ($resource === 'series') {
            $paths = array_merge($paths, $record->chapters()->whereNotNull('image')->pluck('image')->all());
        }
        app(CacheInvalidator::class)->invalidate();
        $record->delete();
        foreach (array_unique(array_filter($paths)) as $path) {
            app(MediaService::class)->delete($path);
        }
        app(CacheInvalidator::class)->invalidate();
    }
}
