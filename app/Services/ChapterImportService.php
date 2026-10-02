<?php

namespace App\Services;

use App\Models\Series;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChapterImportService
{
    private const HEADING = '/^CHAPTER\s+(\d+)\s*[-–:]\s*(.+)$/iu';

    public function preview(string $source, ?int $seriesId = null, array $metadata = []): array
    {
        $blocks = $this->blocks($source);
        $intro = [];
        $chapters = [];
        $current = null;
        foreach ($blocks as $html) {
            $text = trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (preg_match(self::HEADING, $text, $match)) {
                if ($current !== null) {
                    $chapters[] = $current;
                }
                $number = (int) $match[1];
                if ($number < 1 || $number > 1000000) {
                    $this->fail('Chapter numbers must be between 1 and 1,000,000.');
                }
                $current = ['number' => $number, 'title' => trim($match[2]), 'content' => ''];
                if (mb_strlen($current['title']) > 255) {
                    $this->fail('The chapter title is too long.');
                }
            } elseif ($current !== null) {
                $current['content'] .= $html;
            } elseif ($text !== '') {
                $intro[] = $html;
            }
        }
        if ($current !== null) {
            $chapters[] = $current;
        }
        if (! $chapters) {
            $this->fail('No CHAPTER X - Title heading was found.');
        }
        if (count($chapters) > 2000) {
            $this->fail('You can import up to 2,000 chapters at a time.');
        }
        $series = $seriesId ? Series::select(['id', 'title', 'description'])->findOrFail($seriesId) : null;
        $manuscriptTitle = $series ? null : trim(html_entity_decode(strip_tags(array_shift($intro) ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $title = ! empty($metadata['title']) ? $metadata['title'] : ($series?->title ?? $manuscriptTitle);
        if ($title === '' || mb_strlen($title) > 255) {
            $this->fail('The first line before CHAPTER must be the story title (up to 255 characters).');
        }
        $description = trim(html_entity_decode(strip_tags(implode("\n", $intro)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (! empty($metadata['description'])) {
            $description = $metadata['description'];
        }
        $existing = $series ? $series->chapters()->pluck('chapter_number')->all() : [];
        $seen = [];
        $warnings = [];
        $previous = null;
        foreach ($chapters as &$chapter) {
            $number = $chapter['number'];
            $chapter['words'] = preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($chapter['content']));
            $chapter['existing'] = in_array($number, $existing, true);
            if ($chapter['existing']) {
                $warnings[] = "Chapter $number already exists in this story.";
            }
            if (isset($seen[$number])) {
                $warnings[] = "Chapter $number appears more than once in this import.";
            }
            if ($previous !== null && $number !== $previous + 1) {
                $warnings[] = "Non-consecutive chapter numbers: $previous → $number.";
            }
            if ($chapter['words'] === 0) {
                $warnings[] = "Chapter $number has no content.";
            }
            $seen[$number] = true;
            $previous = $number;
        }
        unset($chapter);

        return ['series_id' => $seriesId, 'title' => $title, 'description' => $description, 'chapters' => $chapters, 'warnings' => array_unique($warnings)];
    }

    private function blocks(string $source): array
    {
        if ($source === strip_tags($source)) {
            return array_map(fn ($line) => '<p>'.e($line).'</p>', preg_split('/\R/u', $source));
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><html><body>'.$source.'</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $blocks = [];
        $walk = function ($node) use (&$walk, &$blocks, $dom) {
            if ($node instanceof \DOMText) {
                foreach (preg_split('/\R/u', $node->textContent) as $line) {
                    if (trim($line) !== '') {
                        $blocks[] = '<p>'.e($line).'</p>';
                    }
                }

                return;
            }
            if (! ($node instanceof \DOMElement)) {
                return;
            }
            if (in_array(strtolower($node->tagName), ['div', 'section', 'article', 'body'], true)) {
                foreach ($node->childNodes as $child) {
                    $walk($child);
                }

                return;
            }
            $html = $dom->saveHTML($node);
            if (preg_match('/<br\s*\/?\s*>/i', $html)) {
                // CKEditor soft breaks can contain chapter headings in a single paragraph.
                $parts = preg_split('/<br\s*\/?\s*>/i', $html);
                $hasHeading = false;
                foreach ($parts as $part) {
                    if (preg_match(self::HEADING, trim(html_entity_decode(strip_tags($part), ENT_QUOTES | ENT_HTML5, 'UTF-8')))) {
                        $hasHeading = true;
                    }
                }
                if ($hasHeading) {
                    foreach ($parts as $part) {
                        $blocks[] = '<p>'.preg_replace('~</?(?:p|h[1-6])[^>]*>~i', '', $part).'</p>';
                    }

                    return;
                }
            }
            $blocks[] = $html;
        };
        $walk($dom->getElementsByTagName('body')->item(0));

        return $blocks;
    }

    public function import(array $preview, array $options): Series
    {
        $series = DB::transaction(function () use ($preview, $options) {
            $meta = ['category_id' => $options['category_id'] ?? null, 'status' => $options['status'], 'updated_at' => now(), 'is_demo' => false];
            foreach (['seo_title', 'seo_keywords', 'seo_description'] as $field) {
                if (array_key_exists($field, $options)) {
                    $meta[$field] = $options[$field];
                }
            }
            $oldImages = [];
            if ($preview['series_id']) {
                $series = Series::select(['id', 'title', 'slug', 'description', 'image', 'category_id', 'status'])->lockForUpdate()->findOrFail($preview['series_id']);
                if (! empty($options['title'])) {
                    $meta['title'] = $preview['title'];
                }
                if (! empty($options['slug'])) {
                    $meta['slug'] = $this->seriesSlug($preview['title'], $options['slug'], $series->id);
                }
                if (! empty($options['update_description'])) {
                    $meta['description'] = $preview['description'];
                }
                if (! empty($options['image'])) {
                    $oldImages[] = $series->image;
                    $meta['image'] = $options['image'];
                }
                $series->update($meta);
            } else {
                $series = Series::create($meta + ['created_by' => $options['created_by'] ?? null, 'title' => $preview['title'], 'slug' => $this->seriesSlug($preview['title'], $options['slug'] ?? null), 'description' => $preview['description'], 'image' => $options['image'] ?? null]);
            }
            $tagIds = app(ResourceService::class)->tagIds($options['tags'] ?? []);
            app(ResourceService::class)->syncTags($series, $tagIds);
            $categoryIds = $options['category_ids'] ?? array_filter([$meta['category_id']]);
            app(ResourceService::class)->syncCategories($series, $categoryIds);
            $existing = $series->chapters()->select(['id', 'chapter_number', 'slug', 'image', 'created_by'])->get()->keyBy('chapter_number');
            $rows = [];
            $contents = [];
            $actor = isset($options['created_by']) ? \App\Models\User::findOrFail($options['created_by']) : null;
            $chapterTitles = [];
            foreach ($preview['chapters'] as $chapter) $chapterTitles[$chapter['number']] = $chapter['title'].'-'.$chapter['number'];
            $slugs = $actor ? app(PostSlugService::class)->makeMany($chapterTitles, $actor) : [];
            foreach ($preview['chapters'] as $chapter) {
                $number = $chapter['number'];
                if (($options['duplicates'] ?? 'skip') === 'skip' && (isset($existing[$number]) || isset($rows[$number]))) {
                    continue;
                }
                if (isset($existing[$number]) && isset($options['created_by'])) {
                    abort_unless($actor->managesAllPosts() || $existing[$number]->created_by === $actor->id || ($options['duplicates'] ?? 'skip') === 'skip', 403);
                }
                $slug = $existing[$number]->slug ?? ($slugs[$number] ?? $series->slug.'-'.$number.'-'.Str::lower(Str::random(6)));
                $image = ! empty($options['share_image']) ? $series->image : ($existing[$number]->image ?? null);
                $rows[$number] = ['created_by' => $existing[$number]->created_by ?? $options['created_by'] ?? null, 'type' => 'chapter', 'series_id' => $series->id, 'chapter_number' => $number, 'title' => $chapter['title'], 'slug' => $slug, 'category_id' => $meta['category_id'], 'status' => $meta['status'], 'published_at' => now(), 'image' => $image, 'created_at' => now(), 'updated_at' => now(), 'is_demo' => false];
                $contents[$number] = clean_html($chapter['content']);
                if (isset($existing[$number]) && $existing[$number]->image) {
                    $oldImages[] = $existing[$number]->image;
                }
            }
            foreach (array_chunk(array_values($rows), 200) as $chunk) {
                DB::table('posts')->upsert($chunk, ['series_id', 'chapter_number'], ['title', 'category_id', 'status', 'published_at', 'image', 'updated_at', 'is_demo']);
            }
            $ids = $series->chapters()->whereIn('chapter_number', array_keys($rows))->pluck('id', 'chapter_number');
            $contentRows = [];
            $pivots = [];
            foreach ($ids as $number => $id) {
                $contentRows[] = ['post_id' => $id, 'content' => $contents[$number], 'created_at' => now(), 'updated_at' => now()];
                foreach ($tagIds as $tagId) {
                    $pivots[] = ['post_id' => $id, 'tag_id' => $tagId];
                }
            }
            foreach (array_chunk($contentRows, 100) as $chunk) {
                DB::table('post_contents')->upsert($chunk, ['post_id'], ['content', 'updated_at']);
            }
            DB::table('post_tag')->whereIn('post_id', $ids->values()->all())->delete();
            foreach (array_chunk($pivots, 500) as $chunk) {
                DB::table('post_tag')->insert($chunk);
            }
            DB::table('category_post')->whereIn('post_id', $ids->values()->all())->delete();
            $categoryRows = [];
            foreach ($ids as $id) {
                foreach ($categoryIds as $categoryId) {
                    $categoryRows[] = ['post_id' => $id, 'category_id' => $categoryId];
                }
            }
            foreach (array_chunk($categoryRows, 500) as $chunk) {
                DB::table('category_post')->insert($chunk);
            }
            DB::afterCommit(function () use ($oldImages) {
                foreach (array_unique($oldImages) as $path) {
                    app(MediaService::class)->delete($path);
                }
            });

            return $series;
        });
        app(CacheInvalidator::class)->invalidate();

        return $series;
    }

    private function seriesSlug(string $title, ?string $customSlug, ?int $seriesId = null): string
    {
        $exists = fn (string $slug) => Series::where('slug', $slug)
            ->when($seriesId, fn ($query) => $query->where('id', '!=', $seriesId))->exists();
        if ($customSlug !== null && $customSlug !== '') {
            if ($exists($customSlug)) {
                throw ValidationException::withMessages(['slug' => 'This Series slug is already in use.']);
            }

            return $customSlug;
        }
        $base = substr(Str::slug($title) ?: 'series', 0, 240);
        $base = rtrim($base, '-');
        $slug = $base;
        $suffix = 2;
        while ($exists($slug)) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function fail(string $message): void
    {
        throw ValidationException::withMessages(['content' => $message]);
    }
}
