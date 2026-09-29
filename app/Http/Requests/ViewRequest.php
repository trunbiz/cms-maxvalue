<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['type' => 'required|in:post,series', 'id' => 'required|integer|min:1'];
    }
}
