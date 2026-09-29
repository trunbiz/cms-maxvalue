<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkDeleteRequest;
use App\Http\Requests\ChapterImportRequest;
use App\Http\Requests\ConfirmImportRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Series;
use App\Models\Tag;
use App\Services\ChapterImportService;
use App\Services\MediaService;
use App\Services\ResourceService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ChapterImportController extends Controller
{
    public function create()
    {
        return view('admin.import', ['series' => Series::select(['id', 'title'])->get(), 'categories' => Category::select(['id', 'name'])->get(), 'tags' => Tag::select(['id', 'name'])->get()]);
    }

    public function preview(ChapterImportRequest $request, ChapterImportService $service, MediaService $media)
    {
        $options = $request->validated();
        $preview = $service->preview($options['content'], isset($options['series_id']) ? (int) $options['series_id'] : null);
        unset($options['content']);
        if ($request->hasFile('image')) {
            $options['image'] = $media->upload($request->file('image'), 'imports');
        }
        $token = (string) Str::uuid();
        Cache::put('import.'.$request->user()->id.'.'.$token, compact('preview', 'options'), now()->addHour());

        return view('admin.import-preview', compact('preview', 'token', 'options'));
    }

    public function store(ConfirmImportRequest $request, ChapterImportService $service)
    {
        $key = 'import.'.$request->user()->id.'.'.$request->validated('token');

        return Cache::lock($key.'.lock', 300)->block(5, function () use ($key, $service) {
            $data = Cache::get($key);
            abort_unless($data, 419, 'Bản xem trước đã hết hạn hoặc đã được nhập.');
            $series = $service->import($data['preview'], $data['options']);
            Cache::forget($key);

            return redirect('/admin/posts?series_id='.$series->id)->with('success', 'Đã nhập các chương thành công.');
        });
    }

    public function bulkDelete(BulkDeleteRequest $request, ResourceService $service)
    {
        $posts = Post::select(['id', 'image', 'series_id'])->where('series_id', $request->integer('series_id'))->whereIn('id', $request->validated('ids'))->get();
        abort_unless($posts->count() === count($request->validated('ids')), 422, 'Chương không thuộc truyện đã chọn.');
        foreach ($posts as $post) {
            $service->delete('posts', $post, $request->user());
        }

        return back()->with('success', 'Đã xóa các chương đã chọn.');
    }
}
