<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasModule('settings') ?? false;
    }

    public function rules(): array
    {
        return ['site_name' => 'required|string|max:255', 'seo_description' => 'nullable|string|max:2000', 'ads_txt' => 'nullable|string|max:100000', 'head_html' => [$this->user()->isSuperAdmin() ? 'nullable' : 'prohibited', 'string', 'max:100000'], 'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120', 'favicon' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120'];
    }
}
