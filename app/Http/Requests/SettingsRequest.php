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
        return [
            'site_name' => 'required|string|max:255', 'seo_description' => 'nullable|string|max:2000',
            'publisher_name' => 'nullable|string|max:255', 'contact_email' => 'nullable|email:rfc|max:255',
            'site_url' => ['nullable', 'url', 'max:2048', function ($attribute, $value, $fail) {
                if (parse_url($value, PHP_URL_SCHEME) !== 'https' || parse_url($value, PHP_URL_USER) || parse_url($value, PHP_URL_PASS) || parse_url($value, PHP_URL_QUERY) || parse_url($value, PHP_URL_FRAGMENT) || ! in_array(parse_url($value, PHP_URL_PATH), [null, '', '/'], true)) {
                    $fail('Enter the public HTTPS website origin without a path, credentials or query string.');
                }
            }],
            'adsense_publisher_id' => ['nullable', 'regex:/^ca-pub-\d{16}$/'],
            'consent_reviewed' => 'nullable|boolean',
            'ads_txt' => 'nullable|string|max:100000',
            'head_html' => [$this->user()->isSuperAdmin() ? 'nullable' : 'prohibited', 'string', 'max:100000'],
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120', 'favicon' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $settings = array_merge(app(\App\Services\SiteService::class)->settings(), $this->only('site_name', 'publisher_name', 'contact_email', 'site_url'));
            if (! app(\App\Services\PublisherService::class)->profileComplete($settings)
                && \App\Models\Page::published()->where('content', 'like', '%[[contact_email]]%')->exists()) {
                $validator->errors()->add('publisher_name', 'Published policy pages use this profile. Keep the publisher identity, contact email and public HTTPS address complete, or unpublish those pages first.');
            }
        });
    }
}
