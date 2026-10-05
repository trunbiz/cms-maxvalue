<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class SettingsRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasModule('settings') ?? false; }
    public function rules(): array
    {
        return [
            'head_html' => 'nullable|string|max:100000',
            'permalink_structure' => ['required', Rule::in(['plain', 'day', 'month', 'numeric', 'name', 'custom'])],
            'permalink_custom' => ['nullable', 'required_if:permalink_structure,custom', 'string', 'max:200', 'regex:~^/(?:[a-z0-9-]+|%(?:year|monthnum|day|post_id|postname)%)(?:/(?:[a-z0-9-]+|%(?:year|monthnum|day|post_id|postname)%))*/$~', function ($attribute, $value, $fail) {
                preg_match_all('/%[a-z_]+%/', $value, $tokens);
                if (count($tokens[0]) !== count(array_unique($tokens[0]))) $fail('Use each permalink token only once.');
                if (!str_contains($value, '%postname%') && !str_contains($value, '%post_id%')) $fail('Include %postname% or %post_id% in the custom structure.');
                if (preg_match('~^/(admin|articles|stories|pages|categories|tags|search|liferature)(/|$)~', $value)) $fail('This prefix is reserved.');
            }],
        ];
    }
}
