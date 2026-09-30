@extends('admin.layout')
@section('title',($record->exists?'Edit: ':'New: ').$definition['label'])
@section('actions')
<div class="d-flex flex-wrap gap-2">
    @if($record->exists && ($resource==='pages'||($resource==='posts'&&$record->type==='normal')))
        <a href="/admin/{{ $resource }}/{{ $record->id }}/preview" target="_blank" rel="noopener" class="btn btn-outline-primary icon-button" title="Preview saved content" aria-label="Preview saved content">@include('admin.icon',['name'=>'preview'])</a>
    @endif
    @if($record->exists && $resource==='posts')@include('admin.copy-link',['url'=>post_url($record)])@endif
</div>
@endsection
@section('content')
@if($resource==='pages')<div class="alert alert-info small">Templates support [[site_name]], [[publisher_name]], [[contact_email]], and [[site_url]]. Review the text and your real practices before publishing.</div>@endif
@php
    $isPost=$resource==='posts';
    $isChapter=$isPost && $record->exists && $record->type==='chapter';
    $importMode=$isPost && !$record->exists && (old('compose_mode',request('mode','import'))==='import');
    $groups=[
        'Content'=>array_intersect_key($definition['fields'],array_flip(['name','title','username','password','role_id','modules','description','excerpt','content'])),
        'Organization'=>array_intersect_key($definition['fields'],array_flip(['category_id','tags'])),
        'Featured image'=>array_intersect_key($definition['fields'],array_flip(['image'])),
        'Publishing'=>array_intersect_key($definition['fields'],array_flip(['status','published_at'])),
        'Search & link'=>array_intersect_key($definition['fields'],array_flip(['slug','seo_title','seo_keywords','seo_description'])),
    ];
@endphp
<form method="post" enctype="multipart/form-data" action="/admin/{{ $resource }}{{ $record->exists?'/'.$record->id:'' }}" class="publishing-form" @if($isPost && !$record->exists) data-composer @endif>
    @csrf @if($record->exists) @method('PUT') @endif
    <noscript><div class="alert alert-warning">Enable JavaScript to use the rich text editor, image previews and chapter analysis.</div></noscript>
    @if($isPost)
        <input type="hidden" name="type" value="{{ $isChapter?'chapter':'normal' }}">
        @if($isChapter)
            <div class="alert alert-light border">Editing chapter {{ $record->chapter_number }} of <strong>{{ $record->series?->title }}</strong>. Chapter order is managed by the manuscript import.</div>
        @elseif(!$record->exists)
            <section class="editor-section mb-4">
                <h2 class="section-title">Content type</h2>
                <div class="content-mode-grid">
                    <label class="content-mode"><input class="form-check-input" type="radio" name="compose_mode" value="normal" @checked(!$importMode)><span><strong>Standard article</strong><small>Write and publish one article.</small></span></label>
                    <label class="content-mode"><input class="form-check-input" type="radio" name="compose_mode" value="import" @checked($importMode)><span><strong>Split text into chapters</strong><small>Paste a manuscript, analyze, then save.</small></span></label>
                </div>
            </section>
        @endif
    @endif
    <div class="publishing-grid @if(!$groups['Publishing'] && !$groups['Organization'] && !$groups['Featured image']) publishing-grid-wide @endif">
        <div class="publishing-main">
            @if($isPost && !$record->exists)
                <section class="editor-section mb-4">
                    <h2 class="section-title">Title &amp; description</h2>
                    @include('admin.fields.input',['field'=>'image','label'=>'Featured image'])
                    <div class="mt-3" data-import-image @if(!$importMode) hidden @endif>
                        <input type="hidden" name="share_image" value="0">
                        <label class="form-check"><input class="form-check-input" type="checkbox" name="share_image" value="1" @checked(old('share_image','1')==='1')><span class="form-check-label">Use this cover for all imported chapters</span></label>
                        <p class="form-text mb-0">If no new image is selected, use the selected story's existing cover. The image appears on each chapter page.</p>
                    </div>
                    <hr class="my-4">
                    @include('admin.fields.input',['field'=>'title','label'=>'Title'])
                    @include('admin.fields.input',['field'=>'excerpt','label'=>'Description'])
                    <p class="form-text mb-0">For chapter imports, these fields describe the Series. Leave them blank to use the manuscript introduction.</p>
                </section>
            @endif
            <section class="editor-section" data-standard-content @if($importMode) hidden @endif>
                <h2 class="section-title">Content</h2>
                @foreach($groups['Content'] as $field=>$label) @if(!($isPost && !$record->exists && in_array($field,['title','excerpt']))) @include('admin.fields.input') @endif @endforeach
            </section>
            @if($isPost && !$record->exists)
                <section class="editor-section" data-import-content @if(!$importMode) hidden @endif>
                    <h2 class="section-title">Manuscript</h2>
                    @include('admin.fields.input',['field'=>'series_id','label'=>'Series'])
                    <label class="form-label" for="manuscript">Intro/Description</label>
                    <p class="import-guidance" id="manuscript-help">Mark each chapter with CHAPTER X - Title. Enter a Series title and description above, or put the title on the first line before CHAPTER 1 and its description on the following lines.</p>
                    <textarea class="form-control" id="manuscript" name="manuscript" rows="18" data-editor aria-describedby="manuscript-help">{{ old('manuscript') }}</textarea>
                    <p class="form-text">Images and rich formatting are preserved in the chapter previews.</p>
                    <details class="import-options mt-4"><summary>Import options</summary><div class="pt-3">
                        <label class="form-label" for="duplicates">Existing chapter numbers</label><select class="form-select mb-3" id="duplicates" name="duplicates"><option value="skip">Keep existing chapters (skip duplicates)</option><option value="overwrite">Replace existing chapters</option></select>
                        <label class="form-check mb-2"><input class="form-check-input" type="checkbox" name="update_description" value="1"><span class="form-check-label">Update the selected story description</span></label>
                    </div></details>
                    <button type="button" class="btn btn-outline-primary mt-4" data-analyze>Analyze chapters</button>
                    <div class="mt-3" data-import-message role="status" aria-live="polite"></div>
                </section>
                <section class="editor-section mt-4" data-chapter-preview aria-label="Chapter previews" hidden></section>
            @endif
            @if($groups['Search & link'])
                <details class="editor-section mt-4" data-search-link><summary class="section-title mb-0">Search &amp; link <span class="small fw-normal text-secondary">Optional settings</span></summary><div class="pt-4">@foreach($groups['Search & link'] as $field=>$label) @include('admin.fields.input') @endforeach @if($isPost && !$record->exists)<div data-import-metadata @if(!$importMode) hidden @endif>@include('admin.fields.input',['field'=>'seo_keywords','label'=>'SEO keywords'])<p class="form-text mb-0">Search and link settings apply to the Series. Chapter links are generated automatically.</p></div>@endif</div></details>
            @endif
        </div>
        <aside class="publishing-aside">
            @foreach(['Publishing','Organization','Featured image'] as $group)
                @if($groups[$group] && !($group==='Featured image' && $isPost && !$record->exists))
                    <section class="editor-section mb-4">
                        <h2 class="section-title">{{ $group }}</h2>
                        @foreach($groups[$group] as $field=>$label) @include('admin.fields.input') @endforeach
                        @if($group==='Publishing' && $isChapter && $record->series?->status==='draft')
                            <div class="alert alert-warning small mt-3 mb-0">
                                <p>This story is still a draft. Publishing a chapter also requires its story to be published.</p>
                                <label class="form-check"><input class="form-check-input" type="checkbox" name="publish_series" value="1" @checked(old('publish_series'))><span class="form-check-label">Publish the story with this chapter</span></label>
                                <p class="mt-2 mb-0">Other draft chapters will remain private.</p>
                            </div>
                        @endif
                    </section>
                @endif
            @endforeach
        </aside>
    </div>
    <div class="save-bar"><span class="small text-secondary me-auto" data-save-hint>{{ $record->exists?'Save your changes when ready.':'New content is public by default. Choose Draft to keep it private.' }}</span><a class="btn btn-light" href="/admin/{{ $resource }}">Back</a><button class="btn btn-primary px-4" data-save>{{ $importMode?'Save chapters':'Save changes' }}</button></div>
</form>
@if($isPost && !$record->exists)<form method="post" action="/admin/import" data-import-confirm hidden>@csrf<input type="hidden" name="token" value=""></form>@endif
@if($resource==='menus' && $record->exists) @include('admin.menu-editor') @endif
@endsection
