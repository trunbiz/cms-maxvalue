<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminLanguageRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['language' => 'required|in:vi,en']; }
}
