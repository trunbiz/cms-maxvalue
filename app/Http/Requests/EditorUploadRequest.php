<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditorUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->hasModule('posts') || $this->user()->hasModule('pages'));
    }

    public function rules(): array
    {
        return ['upload' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120']];
    }
}
