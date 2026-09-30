<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrowseRequest;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Series;
use App\Models\Tag;
use App\Services\PublisherService;
use App\Services\SiteService;
use App\Services\SocialPreviewService;

class ReadingController extends Controller
{
    private const POST_COLUMNS = ['id', 'title', 'slug', 'type', 'series_id', 'chapter_number', 'excerpt', 'image', 'category_id', 'published_at', 'seo_title', 'seo_description', 'updated_at', 'status'];

    private const SERIES_COLUMNS = ['id', 'title', 'slug', 'description', 'image', 'category_id', 'status', 'seo_title', 'seo_keywords', 'seo_description', 'updated_at'];

    private function posts()
    {
        return Post::select(self::POST_COLUMNS)->published()->with(['category', 'series']);
    }

    private function seriesQuery()
    {
        return Series::select(self::SERIES_COLUMNS)->published()->with('category')->withCount(['chapters' => fn ($q) => $q->published()]);
    }

    private function pageView(string $view, array $data = [], $entity = null)
    {
        $site = app(SiteService::class);
        $settings = $site->settings();
        $title = $entity?->seo_title ?: ($entity?->title ?? $entity?->name ?? $data['heading'] ?? $settings['site_name'] ?? 'Reading Corner');
        $social = app(SocialPreviewService::class)->metadata($entity, $settings,
            isset($data['chapter']) ? ($data['series'] ?? null) : null,
            $data['content'] ?? ($data['chapter']->content?->content ?? ''));
        $description = $social['description'];
        $canonical = url(request()->path());
        $pagination = array_filter(['page' => request()->integer('page') > 1 ? request()->integer('page') : null, 'series_page' => request()->integer('series_page') > 1 ? request()->integer('series_page') : null]);
        if ($pagination) {
            $canonical .= '?'.http_build_query($pagination);
        }
        if (request()->is('search')) {
            $canonical = url('/search').'?'.http_build_query(array_filter(['q' => request('q')] + $pagination));
        }

        $noindex = ($data['isPreview'] ?? false) || request()->is('search') || ! app(PublisherService::class)->publicUrl(config('app.url'));
        if ($view === 'home' && $data['posts']->isEmpty() && $data['series']->isEmpty()) {
            $noindex = true;
        }
        if ($view === 'listing' && ($data['posts']->total() ?? 0) === 0 && (! isset($data['series']) || $data['series']->total() === 0)) {
            $noindex = true;
        }

        return view('frontend.'.$view, $data + ['settings' => $settings, 'menus' => $site->menus(), 'categories' => $site->categories(), 'seo' => ['title' => $title, 'description' => mb_substr(strip_tags($description), 0, 200), 'canonical' => $canonical, 'image' => $social['image'], 'image_alt' => $social['image_alt'], 'image_type' => $social['image_type'], 'default_image' => $social['default_image'], 'keywords' => $entity?->seo_keywords, 'robots' => $noindex ? 'noindex,follow' : 'index,follow']]);
    }

    public function home()
    {
        $series = $this->seriesQuery()->latest('updated_at')->limit(8)->get();
        $posts = $this->posts()->where('type', 'normal')->latest('published_at')->limit(6)->get();
        $categorySeries = $this->seriesQuery()->latest('updated_at')->limit(60)->get()->groupBy('category_id');

        return $this->pageView('home', compact('series', 'posts', 'categorySeries'));
    }

    public function page(string $slug)
    {
        $page = Page::select(['id', 'title', 'slug', 'content', 'seo_title', 'seo_description', 'updated_at'])->published()->where('slug', $slug)->firstOrFail();

        return $this->pageView('article', ['article' => $page, 'content' => app(PublisherService::class)->renderPage($page->content, app(SiteService::class)->settings())], $page);
    }

    public function category(BrowseRequest $request, string $slug)
    {
        $category = app(SiteService::class)->categories()->firstWhere('slug', $slug);
        abort_unless($category, 404);
        $inCategory = fn ($q) => $q->where('category_id', $category->id)->orWhereHas('categories', fn ($c) => $c->select('categories.id')->where('categories.id', $category->id));
        $series = $this->seriesQuery()->where($inCategory)->latest('updated_at')->paginate(12, ['id'], 'series_page')->withQueryString();
        $posts = $this->posts()->where('type', 'normal')->where($inCategory)->latest('published_at')->paginate(12)->withQueryString();

        return $this->pageView('listing', ['heading' => $category->name, 'description' => $category->description, 'series' => $series, 'posts' => $posts], $category);
    }

    public function tag(BrowseRequest $request, string $slug)
    {
        $tag = Tag::select(['id', 'name', 'slug'])->where('slug', $slug)->firstOrFail();
        $posts = $this->posts()->whereHas('tags', fn ($q) => $q->select('tags.id')->where('tags.id', $tag->id))->latest('published_at')->paginate(18)->withQueryString();
        $series = $this->seriesQuery()->whereHas('tags', fn ($q) => $q->select('tags.id')->where('tags.id', $tag->id))->paginate(12, ['id'], 'series_page')->withQueryString();

        return $this->pageView('listing', ['heading' => 'Tags: '.$tag->name, 'posts' => $posts, 'series' => $series]);
    }

    public function search(BrowseRequest $request)
    {
        $q = trim(mb_substr((string) $request->query('q', ''), 0, 150));
        $posts = $this->posts();
        $series = $this->seriesQuery();
        foreach ([$posts, $series] as $query) {
            if ($q === '') {
                $query->whereRaw('1=0');
            } elseif (config('database.default') === 'mysql') {
                $query->whereFullText('title', $q);
            } else {
                $query->where('title', 'like', '%'.$q.'%');
            }
        }

        return $this->pageView('listing', ['heading' => 'Search: '.$q, 'posts' => $posts->paginate(18)->withQueryString(), 'series' => $series->paginate(12, ['id'], 'series_page')->withQueryString()]);
    }

    public function series(BrowseRequest $request, string $slug)
    {
        $series = $this->seriesQuery()->with('tags')->where('slug', $slug)->firstOrFail();
        $chapters = $this->posts()->where('series_id', $series->id)->orderBy('chapter_number')->paginate(50)->withQueryString();
        $first = $this->posts()->where('series_id', $series->id)->orderBy('chapter_number')->first();
        $schema = ['@context' => 'https://schema.org', '@type' => 'Book', 'name' => $series->title, 'description' => $series->description, 'url' => url('/stories/'.$series->slug), 'inLanguage' => 'en', 'image' => media_url($series->image)];

        return $this->pageView('series', compact('series', 'chapters', 'first', 'schema'), $series);
    }

    public function chapter(string $slug, string $chapterSlug)
    {
        $series = Series::select(['id', 'title', 'slug', 'image', 'description', 'seo_description'])->published()->where('slug', $slug)->firstOrFail();
        $chapter = $this->posts()->with('content')->where('series_id', $series->id)->where('slug', $chapterSlug)->where('type', 'chapter')->firstOrFail();
        $previous = $this->posts()->where('series_id', $series->id)->where('chapter_number', '<', $chapter->chapter_number)->orderByDesc('chapter_number')->first();
        $next = $this->posts()->where('series_id', $series->id)->where('chapter_number', '>', $chapter->chapter_number)->orderBy('chapter_number')->first();
        $chapterLinks = Post::select(['id', 'title', 'slug', 'chapter_number'])
            ->published()->where('series_id', $series->id)->where('type', 'chapter')
            ->orderBy('chapter_number')->get();
        $schema = ['@context' => 'https://schema.org', '@type' => 'Chapter', 'name' => $chapter->title, 'position' => $chapter->chapter_number, 'isPartOf' => ['@type' => 'Book', 'name' => $series->title, 'url' => url('/stories/'.$series->slug)], 'url' => post_url($chapter), 'inLanguage' => 'en'];

        return $this->pageView('chapter', compact('series', 'chapter', 'previous', 'next', 'chapterLinks', 'schema'), $chapter);
    }

    public function post(string $slug)
    {
        $article = $this->posts()->with('content', 'tags')->where('type', 'normal')->where('slug', $slug)->firstOrFail();

        return $this->articleView($article);
    }

    public function articles(BrowseRequest $request)
    {
        return $this->pageView('listing', ['heading' => 'Articles', 'description' => 'Essays, reading notes, and ideas worth spending time with.', 'posts' => $this->posts()->where('type', 'normal')->latest('published_at')->paginate(12)->withQueryString()]);
    }

    private function articleView(Post $article, bool $isPreview = false)
    {
        $content = $article->content?->content ?? '';
        $wordCount = preg_match_all('/[\p{L}\p{N}]+/u', strip_tags($content));
        $readingMinutes = max(1, (int) ceil($wordCount / 220));
        $settings = app(SiteService::class)->settings();
        $schema = ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $article->title, 'description' => $article->seo_description ?: $article->excerpt, 'inLanguage' => 'en', 'mainEntityOfPage' => url('/articles/'.$article->slug), 'publisher' => ['@type' => 'Organization', 'name' => $settings['site_name'] ?? 'Reading Corner', 'url' => url('/')], 'dateModified' => $article->updated_at?->toIso8601String()];
        if ($article->published_at) {
            $schema['datePublished'] = $article->published_at->toIso8601String();
        }
        if ($article->image) {
            $schema['image'] = media_url($article->image);
        }
        $related = $this->posts()->where('type', 'normal')->where('id', '!=', $article->id)->where('category_id', $article->category_id)->latest('published_at')->limit(3)->get();

        return $this->pageView('article', compact('article', 'content', 'readingMinutes', 'schema', 'related', 'isPreview'), $article);
    }

    public function preview(int $id)
    {
        $article = Post::select(self::POST_COLUMNS)->with(['content', 'tags', 'category', 'series'])->where('type', 'normal')->findOrFail($id);

        return response($this->articleView($article, true))->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function pagePreview(int $id)
    {
        $page = Page::select(['id', 'title', 'slug', 'content', 'updated_at', 'seo_title', 'seo_description'])->findOrFail($id);
        $content = app(PublisherService::class)->renderPage($page->content, app(SiteService::class)->settings());

        return response($this->pageView('article', ['article' => $page, 'content' => $content, 'isPreview' => true], $page))->header('Cache-Control', 'private, no-store')->header('X-Robots-Tag', 'noindex, nofollow');
    }

    public function robots()
    {
        $text = "User-agent: *\nDisallow: /admin\nDisallow: /api/\nDisallow: /search\nSitemap: ".url('/sitemap.xml')."\n";

        return response($text, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function ads(SiteService $site)
    {
        $text = app(PublisherService::class)->adsText($site->settings());

        return response($text, $text === '' ? 404 : 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap()
    {
        return response()->stream(function () {
            echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            $emit = fn ($url) => print '<url><loc>'.htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc></url>';
            $emit(url('/'));
            $emit(url('/articles'));
            foreach ([Category::class => 'categories', Page::class => 'pages', Tag::class => 'tags', Series::class => 'stories'] as $class => $prefix) {
                $q = $class::select(['id', 'slug']);
                if ($class === Series::class || $class === Page::class) {
                    $q->published();
                }
                foreach ($q->lazyById(500) as $row) {
                    $emit(url('/'.$prefix.'/'.$row->slug));
                }
            }
            foreach (Post::select(['id', 'slug', 'type', 'series_id'])->published()->with('series')->lazyById(500) as $post) {
                $emit(post_url($post));
            }
            echo '</urlset>';
        }, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
