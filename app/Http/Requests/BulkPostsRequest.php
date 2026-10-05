<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class BulkPostsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasModule('posts') ?? false; }
    public function rules(): array
    {
        return ['action'=>'required|in:published,draft,bin', 'ids'=>'required|array|min:1|max:200', 'ids.*'=>'required|integer|distinct|exists:posts,id'];
    }
}
