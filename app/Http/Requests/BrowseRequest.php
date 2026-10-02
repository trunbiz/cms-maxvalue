<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class BrowseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['status' => 'nullable|in:published,draft,bin', 'category_id' => 'nullable|integer|exists:categories,id', 'created_by' => 'nullable|integer|exists:users,id', 'q' => 'nullable|string|max:150', 'page' => 'nullable|integer|min:1|max:100000', 'series_page' => 'nullable|integer|min:1|max:100000', 'series_id' => 'nullable|integer|min:1'];
    }

    protected function failedValidation(Validator $validator): void
    {
        // Public routes are stateless, so validation cannot flash errors into a session.
        throw new HttpResponseException(response()->json(['message' => 'Invalid search parameters.', 'errors' => $validator->errors()], 422));
    }
}
