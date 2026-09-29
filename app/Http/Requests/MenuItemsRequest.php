<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasModule('menus') ?? false;
    }

    public function rules(): array
    {
        return ['items' => 'present|array|max:200', 'items.*.id' => 'required|integer|not_in:0', 'items.*.parent_id' => 'nullable|integer', 'items.*.label' => 'required|string|max:255', 'items.*.type' => ['required', Rule::in(['url', 'page', 'category', 'series'])], 'items.*.target_id' => 'nullable|integer', 'items.*.url' => ['nullable', 'string', 'max:2048', function ($attribute, $value, $fail) {
            if (str_contains($value, '\\') || ! preg_match('~^(https?://[^\s]+|/(?!/)[^\s]*)$~i', $value)) {
                $fail('URL phải bắt đầu bằng /, http:// hoặc https://.');
            }
        }]];
    }
}
