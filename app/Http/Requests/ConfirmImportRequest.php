<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasModule('posts') ?? false;
    }

    public function rules(): array
    {
        return ['token' => 'required|uuid'];
    }
}
