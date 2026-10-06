@extends('admin.layout')
@section('title',__($record->exists?'Edit: ':'New: ').__($definition['label']))
@section('heading-prefix')
    @if($resource==='posts')<a class="btn btn-light icon-button admin-heading-back" href="/admin/posts" title="{{ __('Back') }}" aria-label="{{ __('Back') }}">@include('admin.icon', ['name'=>'back'])</a>@endif
@endsection
@section('actions')
    <div class="d-flex flex-wrap gap-2">
        @if($record->exists && ($resource==='pages'||($resource==='posts'&&$record->type==='normal')))
            <a href="/admin/{{ $resource }}/{{ $record->id }}/preview" target="_blank" rel="noopener"
               class="btn btn-outline-primary icon-button" title="{{ __('Preview saved content') }}"
               aria-label="{{ __('Preview saved content') }}">@include('admin.icon',['name'=>'preview'])</a>
        @endif
        @if($record->exists && $resource==='posts')
            @include('admin.copy-link',['url'=>post_url($record)])
        @endif
    </div>
@endsection
@section('content')
    @if($resource==='pages')
        <div class="alert alert-info small">{{ __('Templates support [[site_name]], [[publisher_name]], [[contact_email]], and [[site_url]]. Review the text and your real practices before publishing.') }}</div>
    @endif
    @php
        $isPost=$resource==='posts';
        $isChapter=$isPost && $record->exists && $record->type==='chapter';
        $importMode=$isPost && !$record->exists && (old('compose_mode',request('mode','import'))==='import');
        $groups=[
            'Content'=>array_intersect_key($definition['fields'],array_flip(['name','title','username','password','role_id','modules','description','excerpt','content'])),
            'Organization'=>array_intersect_key($definition['fields'],array_flip(['category_id','tags'])),
            'Featured image'=>array_intersect_key($definition['fields'],array_flip(['image'])),
            'Publishing'=>array_intersect_key($definition['fields'],array_flip(['status','published_at'])),
            'Search & link'=>array_intersect_key($definition['fields'],array_flip(['seo_title','seo_keywords','seo_description'])),
        ];
        if ($isPost) {
            unset($groups['Content']['excerpt']);
            $groups['Search & link'] = [];
            $groups['Publishing'] = [];
            unset($groups['Organization']['tags']);
        }
    @endphp
    <form method="post" enctype="multipart/form-data"
          action="/admin/{{ $resource }}{{ $record->exists?'/'.$record->id:'' }}" class="publishing-form"
          @if($isPost && !$record->exists) data-composer data-auto-slug data-author="{{ \Illuminate\Support\Str::slug(auth()->user()->name) }}" data-mode-key="post-compose-mode:{{ auth()->id() }}" data-mode-explicit="{{ session()->hasOldInput('compose_mode') || request()->has('mode') ? 'true' : 'false' }}" @endif>
        @csrf @if($record->exists)
            @method('PUT')
        @endif
        <noscript>
            <div class="alert alert-warning">{{ __('Enable JavaScript to use the rich text editor, image previews and chapter analysis.') }}</div>
        </noscript>
        @if($isPost)
            <input type="hidden" name="status" value="{{ $record->exists ? $record->status : 'published' }}">
            @if($record->exists && $record->published_at)<input type="hidden" name="published_at" value="{{ $record->published_at->format('Y-m-d H:i:s') }}">@endif
            <input type="hidden" name="type" value="{{ $isChapter?'chapter':'normal' }}">
            @if($isChapter)
                <div class="alert alert-light border">Editing chapter {{ $record->chapter_number }} of
                    <strong>{{ $record->series?->title }}</strong>. Chapter order is managed by the manuscript import.
                </div>
            @elseif(!$record->exists)
                <section class="editor-section mb-4">
                    <h2 class="section-title">{{ __('Content type') }}</h2>
                    <div class="content-mode-grid">
                        <label class="content-mode"><input class="form-check-input" type="radio" name="compose_mode"
                                                           value="normal" @checked(!$importMode)><span><strong>{{ __('Standard article') }}</strong><small>{{ __('Write and publish one article.') }}</small></span></label>
                        <label class="content-mode"><input class="form-check-input" type="radio" name="compose_mode"
                                                           value="import" @checked($importMode)><span><strong>{{ __('Split text into chapters') }}</strong><small>{{ __('Paste a manuscript and save. Preview is optional.') }}</small></span></label>
                    </div>
                </section>
            @endif
        @endif
        <div
            class="publishing-grid @if(!$groups['Publishing'] && !$groups['Organization'] && !$groups['Featured image']) publishing-grid-wide @endif">
            <div class="publishing-main">
                @if($isPost)
                    <section class="editor-section mb-4">
                        <h2 class="section-title">{{ __('Title & image') }}</h2>
                        <div class="row gx-4">
                            <div class="col-md-6">
                                @include('admin.fields.input',['field'=>'image','label'=>'Featured image'])
                            </div>
                            <div class="col-md-6" data-title-slug>
                                @include('admin.fields.input',['field'=>'title','label'=>'Title'])
                                @include('admin.fields.input',['field'=>'slug','label'=>$definition['fields']['slug']])
                            </div>
                        </div>
                    </section>
                @endif
                <section class="editor-section" data-standard-content @if($importMode) hidden @endif>
                    <h2 class="section-title">{{ __('Content') }}</h2>
                    @foreach($groups['Content'] as $field=>$label)
                        @if(!($isPost && in_array($field,['title','excerpt'])))
                            @if(in_array($field,['title','name']) && isset($definition['fields']['slug']))
                                <div class="row gx-4">
                                    <div class="col-md-6">@include('admin.fields.input')</div>
                                    <div class="col-md-6">@include('admin.fields.input',['field'=>'slug','label'=>$definition['fields']['slug']])</div>
                                </div>
                            @else
                                @include('admin.fields.input')
                            @endif
                        @endif
                    @endforeach
                </section>
                @if($isPost && !$record->exists)
                    <section class="editor-section" data-import-content @if(!$importMode) hidden @endif>
                        <h2 class="section-title">{{ __('Manuscript') }}</h2>
                        <input type="hidden" name="share_image" value="1">
                        <input type="hidden" name="duplicates" value="skip">
                        <label class="form-label" for="manuscript">{{ __('Manuscript text') }} <span class="text-danger" aria-hidden="true">*</span><span class="visually-hidden">{{ __('Required') }}</span></label>
                        <p class="import-guidance" id="manuscript-help">{{ __('Each manuscript creates a new story. Enter its title above. Mark each chapter with CHAPTER X - Title.') }}</p>
                        <textarea class="form-control" id="manuscript" name="manuscript" rows="18" data-editor
                                  aria-required="true" aria-describedby="manuscript-help">{{ old('manuscript') }}</textarea>
                        <p class="form-text">{{ __('Images and rich formatting are preserved in the chapter previews.') }}</p>
                        <button type="button" class="btn btn-outline-primary mt-4" data-analyze>{{ __('Analyze chapters') }}</button>
                        <div class="mt-3" data-import-message role="status" aria-live="polite"></div>
                    </section>
                    <section class="editor-section mt-4" data-chapter-preview aria-label="{{ __('Chapter previews') }}"
                             hidden></section>
                @endif
                @if($groups['Search & link'])
                    <details class="editor-section mt-4" data-search-link @if($errors->hasAny(['seo_title','seo_keywords','seo_description'])) open @endif>
                        <summary class="section-title mb-0">{{ __('Search & link') }} <span
                                class="small fw-normal text-secondary">{{ __('Optional settings') }}</span></summary>
                        <div class="pt-4">
                            <div class="row gx-4">
                                @foreach(['seo_title','seo_keywords'] as $field)
                                    @if(isset($groups['Search & link'][$field]))
                                        <div class="col-md-6">@include('admin.fields.input',['label'=>$groups['Search & link'][$field]])</div>
                                    @elseif($field==='seo_keywords' && $isPost && !$record->exists)
                                        <div class="col-md-6" data-import-metadata @if(!$importMode) hidden @endif>
                                            @include('admin.fields.input',['field'=>'seo_keywords','label'=>'SEO keywords'])
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            @if(isset($groups['Search & link']['seo_description']))
                                @include('admin.fields.input',['field'=>'seo_description','label'=>$groups['Search & link']['seo_description']])
                            @endif
                            @if($isPost && !$record->exists)
                                <p class="form-text mb-0">{{ __('For chapter imports, search and link settings apply to the Series. Chapter links are generated automatically.') }}</p>
                            @endif
                        </div>
                    </details>
                @endif
            </div>
            <aside class="publishing-aside">
                @foreach(['Publishing','Organization','Featured image'] as $group)
                    @if($groups[$group] && !($group==='Featured image' && $isPost))
                        <section class="editor-section mb-4">
                            <h2 class="section-title">{{ __($group) }}</h2>
                            @foreach($groups[$group] as $field=>$label)
                                @include('admin.fields.input')
                            @endforeach
                            @if($group==='Publishing' && $isChapter && $record->series?->status==='draft')
                                <div class="alert alert-warning small mt-3 mb-0">
                                    <p>{{ __('This story is still a draft. Publishing a chapter also requires its story to be published.') }}</p>
                                    <label class="form-check"><input class="form-check-input" type="checkbox"
                                                                     name="publish_series"
                                                                     value="1" @checked(old('publish_series'))><span
                                            class="form-check-label">{{ __('Publish the story with this chapter') }}</span></label>
                                    <p class="mt-2 mb-0">{{ __('Other draft chapters will remain private.') }}</p>
                                </div>
                            @endif
                        </section>
                    @endif
                @endforeach
            </aside>
        </div>
        <div class="mt-3" data-save-message role="status" aria-live="polite" hidden></div>
        <div class="save-bar"><span class="small text-secondary me-auto"
                                    data-save-hint>{{ $record->exists ? __('Save your changes when ready.') : '' }}</span>
            <button type="submit" class="btn btn-primary px-4 order-2" data-save @if($isPost && !$record->exists) name="status" value="published" data-save-status="published" @endif>{{ __($importMode?'Save chapters':'Save changes') }}</button>
            @if($isPost && !$record->exists)
                <button type="submit" class="btn btn-success order-3" name="status" value="published" data-save-status="published" data-publish-copy @if($importMode) hidden disabled @endif>{{ __('Publish and copy link') }}</button>
                <button type="submit" class="btn btn-outline-secondary order-1" name="status" value="draft" data-save-draft data-save-status="draft">{{ __('Save draft') }}</button>
            @else
                @if($isPost)
                    <button type="submit" class="btn btn-success order-3" data-save-copy>{{ __('Save and copy link') }}</button>
                @endif
                <a class="btn btn-light order-1" href="/admin/{{ $resource }}">{{ __('Back') }}</a>
            @endif
        </div>
    </form>
    @if($isPost && !$record->exists)
        <form method="post" action="/admin/import" data-import-confirm hidden>@csrf<input type="hidden" name="token"
                                                                                          value=""></form>
    @endif
    @if($resource==='menus' && $record->exists)
        @include('admin.menu-editor')
    @endif
@endsection
