<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrowseRequest;
use App\Http\Requests\ResourceRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Role;
use App\Models\Series;
use App\Models\Tag;
use App\Models\User;
use App\Services\ResourceService;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    private function definition(string $resource): array
    {
        $def = config('cms.resources.'.$resource);
        abort_unless($def, 404);

        return $def;
    }

    private function query(string $resource)
    {
        $model = $this->definition($resource)['model'];

        return $model::query()->select(array_merge(['id'], (new $model)->getFillable(), ['created_at', 'updated_at']))
            ->when(in_array($resource, ['posts', 'series']) && !auth()->user()->managesAllPosts(), fn ($q) => $q->where('created_by', auth()->id()));
    }

    public function index(BrowseRequest $request, string $resource)
    {
        $definition = $this->definition($resource);
        $q = $this->query($resource);
        $field = isset($definition['fields']['title']) ? 'title' : 'name';
        if ($request->filled('q')) {
            $term = '%'.$request->validated('q').'%';
            $q->where(function ($query) use ($field, $resource, $term) {
                $query->where($field, 'like', $term);
                if ($resource === 'users') {
                    $query->orWhere('username', 'like', $term);
                }
            });
        }
        if ($resource === 'series') {
            $q->withCount('chapters');
        }
        if ($resource === 'tags') {
            $q->withCount('posts');
        }
        if ($resource === 'posts') {
            $q->with(['series', 'creator']);
            if ($request->filled('created_from')) {
                $q->where('created_at', '>=', \Carbon\Carbon::parse($request->validated('created_from'))->startOfDay());
            }
            if ($request->filled('created_until')) {
                $q->where('created_at', '<', \Carbon\Carbon::parse($request->validated('created_until'))->startOfDay()->addDay());
            }
            if ($request->filled('series_id')) {
                $q->where('series_id', $request->integer('series_id'))->orderBy('chapter_number');
            }
        }
        if (in_array($resource, ['posts', 'series'])) {
            foreach (['status', 'created_by'] as $filter) {
                if ($request->filled($filter)) $q->where($filter, $request->validated($filter));
            }
            if ($request->filled('category_id')) $q->whereHas('categories', fn ($c) => $c->where('categories.id', $request->integer('category_id')));
        }
        $records = $q->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.index', compact('resource', 'definition', 'records') + ['categories' => Category::select(['id','name'])->get(), 'series' => Series::select(['id','title'])->when(!auth()->user()->managesAllPosts(), fn ($q) => $q->where('created_by', auth()->id()))->get(), 'authors' => User::select(['id','name','role_id'])->when(!auth()->user()->managesAllPosts(), fn ($q) => $q->whereKey(auth()->id()))->get()]);
    }

    public function create(string $resource)
    {
        $model = $this->definition($resource)['model'];

        $record = new $model;
        if (isset($this->definition($resource)['fields']['status'])) {
            $record->status = 'published';
        }

        return $this->form($resource, $record);
    }

    public function edit(string $resource, int $id)
    {
        return $this->form($resource, $this->query($resource)->findOrFail($id));
    }

    private function form(string $resource, $record)
    {
        $definition = $this->definition($resource);
        if ($resource === 'posts') {
            $record->load('content', 'tags', 'categories', 'series');
        }
        if ($resource === 'series') {
            $record->load('tags', 'categories');
        }
        if ($resource === 'menus') {
            $record->load('items');
        }

        return view('admin.form', compact('resource', 'definition', 'record') + ['roles' => Role::select(['id', 'name'])->get(), 'categories' => Category::select(['id', 'name'])->get(), 'tags' => Tag::select(['id', 'name'])->get(), 'series' => Series::select(['id', 'title'])->when(!auth()->user()->managesAllPosts(), fn ($q) => $q->where('created_by', auth()->id()))->orderBy('title')->get(), 'pages' => \App\Models\Page::select(['id', 'title'])->get()]);
    }

    public function store(ResourceRequest $request, string $resource, ResourceService $service)
    {
        $model = $this->definition($resource)['model'];
        $record = $service->save($resource, new $model, $request->validated(), $request->user());

        if ($resource === 'posts' && $request->expectsJson()) {
            return response()->json(['url' => post_url($record), 'edit_url' => '/admin/posts/'.$record->id.'/edit'], 201);
        }

        return redirect($resource === 'posts' ? '/admin/posts' : '/admin/'.$resource.'/'.$record->id.'/edit')->with('success', 'Created successfully.');
    }

    public function update(ResourceRequest $request, string $resource, int $id, ResourceService $service)
    {
        $record = $service->save($resource, $this->query($resource)->findOrFail($id), $request->validated(), $request->user());

        if ($resource === 'posts' && $request->expectsJson()) {
            return response()->json(['url' => post_url($record), 'edit_url' => '/admin/posts/'.$record->id.'/edit']);
        }

        return ($resource === 'posts' ? redirect('/admin/posts') : back())->with('success', 'Changes saved.');
    }

    public function destroy(Request $request, string $resource, int $id, ResourceService $service)
    {
        $service->delete($resource, $this->query($resource)->findOrFail($id), $request->user());

        return redirect('/admin/'.$resource)->with('success', 'Deleted successfully.');
    }

    public function bulk(\App\Http\Requests\BulkPostsRequest $request, \App\Services\CacheInvalidator $cache)
    {
        $data = $request->validated();
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $data) {
            $posts = Post::select(['id', 'created_by', 'published_at'])->whereIn('id', $data['ids'])->lockForUpdate()->get();
            abort_unless($request->user()->managesAllPosts() || $posts->every(fn ($post) => $post->created_by === $request->user()->id), 403);
            foreach ($posts as $post) {
                $post->status = $data['action'];
                if ($data['action'] === 'published' && (!$post->published_at || $post->published_at->isFuture())) $post->published_at = now();
                $post->save();
            }
        });
        $cache->invalidate();
        return back()->with('success', 'Selected posts updated.');
    }

    public function dashboard()
    {
        return view('admin.dashboard', ['counts' => ['Articles' => $this->query('posts')->count(), 'Stories' => Series::count(), 'Categories' => Category::count(), 'Users' => User::count()], 'posts' => $this->query('posts')->latest()->limit(10)->get()]);
    }
}
