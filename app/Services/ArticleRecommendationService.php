<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ArticleRecommendationService
{
    private function query(bool $articlesOnly = true): Builder
    {
        return Post::select(['id', 'title', 'slug', 'slug_is_custom', 'type', 'series_id', 'excerpt', 'image', 'category_id', 'published_at', 'created_at'])
            ->published()->when($articlesOnly, fn (Builder $query) => $query->where('type', 'normal'))->with(['category', 'series']);
    }

    public function latest(int $limit = 10): Collection
    {
        return $this->query()->latest('published_at')->limit($limit)->get();
    }

    public function suggestions(int $limit = 10): Collection
    {
        $posts = $this->latest($limit);
        if ($posts->count() < $limit) {
            $posts = $posts->concat($this->query(false)->where('type', 'chapter')
                ->latest('published_at')->limit($limit - $posts->count())->get());
        }

        return $posts;
    }

    public function related(Post $article, int $limit = 10): Collection
    {
        $article->loadMissing(['tags', 'categories']);
        $tagIds = $article->tags->modelKeys();
        $categoryIds = $article->categories->pluck('id')->push($article->category_id)->filter()->unique()->values()->all();
        $isChapter = $article->type === 'chapter';
        if ($isChapter && $article->series_id) {
            $series = \App\Models\Series::select(['id', 'category_id'])->with(['tags', 'categories'])->find($article->series_id);
            if ($series) {
                $tagIds = array_values(array_unique(array_merge($tagIds, $series->tags->modelKeys())));
                $categoryIds = array_values(array_unique(array_merge($categoryIds, $series->categories->pluck('id')->push($series->category_id)->filter()->all())));
            }
        }
        $base = $this->query(! $isChapter)->where('id', '!=', $article->id);
        $related = collect();
        if ($tagIds || $categoryIds) {
            $related = (clone $base)->where(function (Builder $query) use ($tagIds, $categoryIds, $isChapter) {
                $query->whereIn('category_id', $categoryIds)
                    ->orWhereHas('categories', fn (Builder $categories) => $categories->whereIn('categories.id', $categoryIds))
                    ->orWhereHas('tags', fn (Builder $tags) => $tags->whereIn('tags.id', $tagIds));
                if ($isChapter) {
                    $query->orWhereHas('series', function (Builder $series) use ($tagIds, $categoryIds) {
                        $series->where(function (Builder $topics) use ($tagIds, $categoryIds) {
                            $topics->whereIn('category_id', $categoryIds)
                                ->orWhereHas('categories', fn (Builder $categories) => $categories->whereIn('categories.id', $categoryIds))
                                ->orWhereHas('tags', fn (Builder $tags) => $tags->whereIn('tags.id', $tagIds));
                        });
                    });
                }
            })->latest('published_at')->limit($limit)->get();
        }
        if ($related->count() < $limit) {
            $related = $related->concat((clone $base)->whereNotIn('id', $related->pluck('id'))
                ->latest('published_at')->limit($limit - $related->count())->get());
        }

        return $related;
    }
}
