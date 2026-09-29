<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChapterImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasModule('posts') ?? false;
    }

    public function rules(): array
    {
        return ['content' => 'required|string|max:5000000', 'series_id' => 'nullable|integer|exists:series,id', 'category_id' => 'nullable|exists:categories,id', 'status' => 'required|in:draft,published', 'duplicates' => 'required|in:skip,overwrite', 'update_description' => 'nullable|boolean', 'share_image' => 'nullable|boolean', 'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120', 'tags' => 'nullable|array|max:100', 'tags.*' => 'string|max:100'];
    }
}
