<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteRequest;
use App\Http\Requests\ChapterImportRequest;
use App\Http\Requests\ConfirmImportRequest;
use App\Models\Post;
use App\Models\Series;
use App\Services\ChapterImportService;
use App\Services\MediaService;
use App\Services\ResourceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ChapterImportController extends Controller
{
    public function saveDirect(ChapterImportRequest $request, ChapterImportService $service, MediaService $media)
    {
        $options = $request->validated();
        $preview = $service->preview($options['content'], isset($options['series_id']) ? (int) $options['series_id'] : null, $options);
        unset($options['content']);
        $options['created_by'] = $request->user()->id;
        if ($request->hasFile('image')) $options['image'] = $media->upload($request->file('image'), 'imports');
        if (!empty($options['image_path'])) {
            abort_unless(in_array($options['image_path'], session('featured_uploads', []), true), 422);
            $options['image'] = $options['image_path'];
        }
        $series = $service->import($preview, $options);
        return redirect('/admin/posts?series_id='.$series->id)->with('success', 'Chapters imported successfully.');
    }

    public function create()
    {
        return redirect('/admin/posts/create?mode=import');
    }

    public function preview(ChapterImportRequest $request, ChapterImportService $service, MediaService $media)
    {
        $options = $request->validated();
        $options['created_by'] = $request->user()->id;
        $preview = $service->preview($options['content'], isset($options['series_id']) ? (int) $options['series_id'] : null, $options);
        unset($options['content']);
        if ($request->hasFile('image')) {
            $options['image'] = $media->upload($request->file('image'), 'imports');
        }
        $coverImage = ! empty($options['share_image']) ? ($options['image'] ?? (isset($options['series_id']) ? Series::whereKey($options['series_id'])->value('image') : null)) : null;
        $token = (string) Str::uuid();
        Cache::put('import.'.$request->user()->id.'.'.$token, compact('preview', 'options'), now()->addHour());

        if ($request->expectsJson()) {
            return response()->json(['token' => $token, 'html' => view('admin.chapter-preview', compact('preview', 'options', 'coverImage'))->render()]);
        }

        return view('admin.import-preview', compact('preview', 'token', 'options', 'coverImage'));
    }

    public function store(ConfirmImportRequest $request, ChapterImportService $service)
    {
        $key = 'import.'.$request->user()->id.'.'.$request->validated('token');

        return Cache::lock($key.'.lock', 300)->block(5, function () use ($key, $service) {
            $data = Cache::get($key);
            abort_unless($data, 419, 'This preview has expired or has already been imported.');
            $series = $service->import($data['preview'], $data['options']);
            Cache::forget($key);

            return redirect('/admin/posts?series_id='.$series->id)->with('success', 'Chapters imported successfully.');
        });
    }

    public function bulkDelete(BulkDeleteRequest $request, ResourceService $service)
    {
        $posts = Post::select(['id', 'image', 'series_id', 'created_by'])->where('series_id', $request->integer('series_id'))->whereIn('id', $request->validated('ids'))->when(!$request->user()->managesAllPosts(), fn ($q) => $q->where('created_by', $request->user()->id))->get();
        abort_unless($posts->count() === count($request->validated('ids')), 422, 'A selected chapter does not belong to this story.');
        foreach ($posts as $post) {
            $service->delete('posts', $post, $request->user());
        }

        return back()->with('success', 'Selected chapters deleted.');
    }
}
