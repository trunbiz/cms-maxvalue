<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $r = $this->route('resource');

        return $this->user()?->hasModule($r === 'series' ? 'posts' : $r) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (in_array($this->route('resource'), ['posts', 'series']) && ($this->has('category_selection') || $this->has('category_ids'))) {
            $ids = $this->input('category_ids', []);
            $this->merge(['category_ids' => $ids, 'category_id' => is_array($ids) ? ($ids[0] ?? null) : null]);
        }
        if ($this->route('resource') === 'posts' && $this->route('id')) {
            $post = \App\Models\Post::select(['id', 'type', 'series_id', 'chapter_number'])->find($this->route('id'));
            if ($post?->type === 'chapter') {
                $this->merge(['type' => 'chapter', 'series_id' => $post->series_id, 'chapter_number' => $post->chapter_number]);
            }
        }
        if (array_key_exists('slug', config('cms.resources.'.$this->route('resource').'.fields', [])) && ! $this->filled('slug')) {
            $this->merge(['slug' => Str::slug($this->input('title') ?: $this->input('name'))]);
        }
    }

    public function rules(): array
    {
        $r = $this->route('resource');
        $id = $this->route('id');
        $base = ['name' => 'required|string|max:255', 'title' => 'required|string|max:255', 'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique($r, 'slug')->ignore($id)], 'description' => 'nullable|string|max:30000', 'excerpt' => 'nullable|string|max:30000', 'content' => 'required|string|max:3000000', 'seo_title' => 'nullable|string|max:255', 'seo_keywords' => 'nullable|string|max:2000', 'seo_description' => 'nullable|string|max:2000', 'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120', 'category_id' => 'nullable|exists:categories,id', 'status' => ['required', Rule::in(['draft', 'published'])], 'published_at' => 'nullable|date', 'tags' => 'nullable|array|max:100', 'type' => ['required', Rule::in(['normal', 'chapter'])], 'series_id' => 'required_if:type,chapter|nullable|exists:series,id', 'chapter_number' => ['required_if:type,chapter', 'nullable', 'integer', 'min:1', Rule::unique('posts', 'chapter_number')->where('series_id', $this->input('series_id'))->ignore($id)], 'username' => ['required', 'alpha_dash', 'max:255', Rule::unique('users')->ignore($id)], 'password' => [$id ? 'nullable' : 'required', 'string', 'min:8', 'max:255'], 'role_id' => 'required|exists:roles,id', 'modules' => 'nullable|array'];
        $rules = array_intersect_key($base, config('cms.resources.'.$r.'.fields', []));
        if (in_array($r, ['posts', 'series'])) {
            $rules['category_ids'] = 'sometimes|array|max:100';
            $rules['category_ids.*'] = 'integer|distinct|exists:categories,id';
        }
        if ($r === 'posts') {
            $rules['image_path'] = 'nullable|string|max:255';
            $rules['status'] = ['required', Rule::in(['draft', 'published', 'bin'])];
            $rules['slug'] = ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*(?:\/[a-z0-9]+(?:-[a-z0-9]+)*)?$/'];
            $rules['publish_series'] = 'nullable|boolean';
            $rules['type'] = ['required', Rule::in($id ? ['normal', 'chapter'] : ['normal'])];
            $rules['chapter_number'] = $base['chapter_number'];
        }
        if ($r === 'roles') {
            $rules['name'] = ['required', 'string', 'max:255', Rule::unique('roles')->ignore($id)];
            $rules['modules.*'] = [Rule::in(array_keys(config('modules')))];
        }
        if (isset($rules['tags'])) {
            $rules['tags.*'] = 'string|max:100';
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->route('resource') === 'posts' && $this->input('type') === 'chapter' && $this->input('status') === 'published'
                && ! $this->boolean('publish_series') && \App\Models\Series::whereKey($this->input('series_id'))->where('status', 'draft')->exists()) {
                $validator->errors()->add('status', 'The story is still a draft. Select "Publish the story with this chapter" or publish the story first.');
            }
            if ($this->route('resource') === 'pages' && $this->input('status') === 'published'
                && preg_match('/\[\[(publisher_name|contact_email|site_url)\]\]/', (string) $this->input('content'))
                && ! app(\App\Services\PublisherService::class)->profileComplete(app(\App\Services\SiteService::class)->settings())) {
                $validator->errors()->add('status', 'Complete the publisher profile in Settings before publishing a page that uses publisher placeholders.');
            }
        });
    }
}
