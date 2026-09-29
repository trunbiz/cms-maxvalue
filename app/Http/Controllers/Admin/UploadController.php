<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EditorUploadRequest;
use App\Services\MediaService;

class UploadController extends Controller
{
    public function __invoke(EditorUploadRequest $request, MediaService $media)
    {
        $path = $media->upload($request->file('upload'), 'editor');

        return response()->json(['url' => $media->url($path)]);
    }
}
