<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChapterImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasModule('posts') ?? false;
    }

    public function rules(): array
    {
        return ['title' => 'nullable|string|max:255', 'description' => 'nullable|string|max:30000', 'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('series', 'slug')->ignore($this->input('series_id'))], 'seo_title' => 'nullable|string|max:255', 'seo_keywords' => 'nullable|string|max:2000', 'seo_description' => 'nullable|string|max:2000', 'content' => 'required|string|max:5000000', 'series_id' => 'nullable|integer|exists:series,id', 'category_id' => 'nullable|exists:categories,id', 'category_ids' => 'sometimes|array|max:100', 'category_ids.*' => 'integer|distinct|exists:categories,id', 'status' => 'required|in:draft,published', 'duplicates' => 'required|in:skip,overwrite', 'update_description' => 'nullable|boolean', 'share_image' => 'nullable|boolean', 'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120', 'tags' => 'nullable|array|max:100', 'tags.*' => 'string|max:100'];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('category_selection') || $this->has('category_ids')) {
            $ids = $this->input('category_ids', []);
            $this->merge(['category_ids' => $ids, 'category_id' => is_array($ids) ? ($ids[0] ?? null) : null]);
        }
    }
}
