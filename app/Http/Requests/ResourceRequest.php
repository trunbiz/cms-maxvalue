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
        if ($r === 'roles') {
            $rules['name'] = ['required', 'string', 'max:255', Rule::unique('roles')->ignore($id)];
            $rules['modules.*'] = [Rule::in(array_keys(config('modules')))];
        }
        if (isset($rules['tags'])) {
            $rules['tags.*'] = 'string|max:100';
        }

        return $rules;
    }
}
