<?php

namespace App\Http\Requests;

use App\Services\PublisherService;
use App\Services\SiteService;
use Illuminate\Foundation\Http\FormRequest;

class PublishPagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasModule('settings') && $this->user()?->hasModule('pages');
    }

    public function rules(): array
    {
        return ['reviewed' => 'required|accepted'];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! app(PublisherService::class)->profileComplete(app(SiteService::class)->settings())) {
                $validator->errors()->add('reviewed', 'Complete and save the publisher profile before publishing these pages.');
            }
        });
    }
}
