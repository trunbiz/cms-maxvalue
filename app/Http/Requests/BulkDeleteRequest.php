<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasModule('posts') ?? false;
    }

    public function rules(): array
    {
        return ['series_id' => 'required|exists:series,id', 'ids' => 'required|array|max:200', 'ids.*' => 'required|integer|distinct|exists:posts,id'];
    }
}
