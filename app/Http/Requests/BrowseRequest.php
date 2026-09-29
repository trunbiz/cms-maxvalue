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
        return ['q' => 'nullable|string|max:150', 'page' => 'nullable|integer|min:1|max:100000', 'series_page' => 'nullable|integer|min:1|max:100000', 'series_id' => 'nullable|integer|min:1'];
    }

    protected function failedValidation(Validator $validator): void
    {
        // Public routes are stateless, so validation cannot flash errors into a session.
        throw new HttpResponseException(response()->json(['message' => 'Tham số tìm kiếm không hợp lệ.', 'errors' => $validator->errors()], 422));
    }
}
