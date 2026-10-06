@extends('admin.layout')
@section('title', __('Settings'))
@section('content')
@php
    $mode = old('permalink_structure', $settings['permalink_structure'] ?? 'name');
    if (!in_array($mode, ['plain', 'day', 'month', 'numeric', 'name', 'author', 'custom'])) $mode = 'name';
    $presets = \App\Services\PermalinkService::PRESETS;
    $custom = (string) old('permalink_custom', ($settings['permalink_custom'] ?? null) ?: $presets['name']);
    $structure = $mode === 'custom' ? $custom : ($presets[$mode] ?? $presets['name']);
    $tokens = ['%year%' => now()->format('Y'), '%monthnum%' => now()->format('m'), '%day%' => now()->format('d'), '%post_id%' => '123', '%postname%' => 'sample-post', '%author%' => 'sample-author'];
    $labels = ['plain' => 'Plain', 'day' => 'Day and name', 'month' => 'Month and name', 'numeric' => 'Numeric', 'name' => 'Post name', 'author' => 'Post name and author', 'custom' => 'Custom structure'];
@endphp
<form method="post" action="/admin/settings" class="admin-settings-form">
@csrf @method('PUT')
<section class="editor-section mb-4" data-permalink-settings data-permalink-presets="{{ json_encode($presets) }}" data-permalink-tokens="{{ json_encode($tokens) }}" data-base-url="{{ url('/') }}" data-custom-structure="{{ $custom }}" aria-labelledby="permalink-title">
    <h2 id="permalink-title" class="section-title">{{ __('Post permalinks') }}</h2>
    <p class="text-secondary small mb-4">{{ __('Choose how article links appear. The structure and example below update with your selection.') }}</p>
    <div class="permalink-options">
@foreach($labels as $value=>$label)
        <label class="permalink-option">
            <input class="form-check-input" type="radio" name="permalink_structure" value="{{ $value }}" @checked($mode === $value)>
            <span><strong>{{ __($label) }}</strong><code>{{ strtr($presets[$value] ?? $custom, $tokens) }}</code></span>
        </label>
@endforeach
    </div>
    <div class="permalink-structure mt-4">
        <label for="permalink_custom" class="form-label">{{ __('Custom structure') }}</label>
        <input id="permalink_custom" name="permalink_custom" class="form-control" value="{{ $structure }}" data-permalink-input @disabled($mode !== 'custom') aria-describedby="permalink-help" placeholder="/%year%/%postname%/">
        <p id="permalink-help" class="form-text">{{ __('Select Custom structure to edit the pattern, or click a token below.') }}</p>
        <div class="d-flex flex-wrap gap-2" aria-label="{{ __('Available tokens') }}">@foreach(array_keys($tokens) as $token)<button type="button" class="permalink-token" data-permalink-token="{{ $token }}">{{ $token }}</button>@endforeach</div>
        <div class="permalink-example mt-3"><span>{{ __('Example URL') }}</span><code data-permalink-preview aria-live="polite">{{ url('/').strtr($structure, $tokens) }}</code></div>
        <p class="form-text mb-0 mt-3">{{ __('Chapter links stay under their story.') }}</p>
    </div>
</section>
<section class="editor-section mb-4"><label class="section-title" for="head_html">{{ __('Custom head HTML') }}</label><textarea class="form-control" id="head_html" name="head_html" rows="7">{{ old('head_html', $settings['head_html'] ?? '') }}</textarea></section>
<button class="btn btn-primary px-4">{{ __('Save settings') }}</button>
</form>
@endsection
