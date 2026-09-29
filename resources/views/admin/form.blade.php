@extends('admin.layout')
@section('title',($record->exists?'Edit: ':'New: ').$definition['label'])
@section('actions')
@if($record->exists && ($resource==='pages'||($resource==='posts'&&$record->type==='normal')))
<a href="/admin/{{ $resource }}/{{ $record->id }}/preview" target="_blank" rel="noopener" class="btn btn-outline-primary">Preview saved content ↗</a>
@endif
@endsection
@section('content')
@if($resource==='pages')<div class="alert alert-info small">Publication templates support [[site_name]], [[publisher_name]], [[contact_email]], and [[site_url]]. These are filled from Settings when displayed. Review the text and your real practices before publishing.</div>@endif
@if($resource==='posts')<div class="alert alert-info small">Write an original article, add an accurate byline and description, and save a draft to preview it. Only published articles whose publication date has arrived appear on the public website.</div>@endif
<form method="post" enctype="multipart/form-data" action="/admin/{{ $resource }}{{ $record->exists?'/'.$record->id:'' }}" class="card border-0 shadow-sm p-4">@csrf @if($record->exists) @method('PUT') @endif
<div class="row g-4">@foreach($definition['fields'] as $field=>$label)
@php($value=old($field,$field==='content' && $resource==='posts' ? $record->content?->content : $record->getAttribute($field)))
<div class="{{ in_array($field,['content','description','excerpt','modules','tags'])?'col-12':'col-md-6' }}"><label class="form-label" for="field-{{ $field }}">{{ $label }}</label>
@if($field==='modules')<div class="d-flex flex-wrap gap-3">@foreach(config('modules') as $key=>$module)<label class="form-check"><input class="form-check-input" type="checkbox" name="modules[]" value="{{ $key }}" @checked(in_array($key,old('modules',$record->modules??[])))><span class="form-check-label">{{ $module }}</span></label>@endforeach</div>
@elseif($field==='tags')<select class="form-select" id="field-tags" name="tags[]" multiple data-tags>@foreach($tags as $tag)<option value="{{ $tag->id }}" @selected(in_array($tag->id,old('tags',$record->exists?$record->tags->pluck('id')->all():[])))>{{ $tag->name }}</option>@endforeach</select><div class="input-group mt-2"><input class="form-control" data-new-tag placeholder="Enter a new tag" aria-label="New tag name"><button type="button" class="btn btn-outline-secondary" data-add-tag>Add tag</button></div><small class="text-secondary">Hold Ctrl or Command to select multiple tags.</small>
@elseif(in_array($field,['role_id','category_id','series_id']))<select class="form-select" name="{{ $field }}" id="field-{{ $field }}"><option value="">— Select —</option>@foreach($field==='role_id'?$roles:($field==='category_id'?$categories:$series) as $option)<option value="{{ $option->id }}" @selected((string)$value===(string)$option->id)>{{ $option->name ?? $option->title }}</option>@endforeach</select>
@elseif($field==='status')<select class="form-select" name="status" id="field-status"><option value="draft" @selected($value==='draft')>Draft</option><option value="published" @selected($value==='published')>Publish</option></select>
@elseif($field==='type')<div class="d-flex gap-4">@foreach(['normal'=>'Standard article','chapter'=>'Single chapter'] as $key=>$text)<label><input type="radio" name="type" value="{{ $key }}" @checked(($value??'normal')===$key)> {{ $text }}</label>@endforeach</div><a class="small" href="/admin/import">Import a manuscript and split it into chapters →</a>
@elseif($field==='image')<input class="form-control" type="file" name="image" id="field-image" accept="image/jpeg,image/png,image/webp,image/gif">@if($record->image)<img src="{{ media_url($record->image) }}" loading="lazy" class="mt-2 rounded" width="90" alt="Current image">@endif
@elseif(in_array($field,['content','description','excerpt','seo_description']))<textarea class="form-control" id="field-{{ $field }}" name="{{ $field }}" rows="{{ $field==='content'?14:4 }}" @if($field==='content') data-editor @endif>{{ $field==='content' ? content_html($value) : $value }}</textarea>
@else<input class="form-control" id="field-{{ $field }}" name="{{ $field }}" type="{{ $field==='password'?'password':($field==='chapter_number'?'number':($field==='published_at'?'datetime-local':'text')) }}" value="{{ $field==='password'?'':($field==='published_at' && $value instanceof \Carbon\Carbon ? $value->format('Y-m-d\TH:i'):$value) }}" @if($field==='password') autocomplete="new-password" @endif>@if($field==='password' && $record->exists)<small class="text-secondary">Leave blank to keep the current password.</small>@endif
@endif</div>@endforeach</div><div class="d-flex gap-2 mt-4"><button class="btn btn-primary px-4">Save changes</button><a class="btn btn-light" href="/admin/{{ $resource }}">Back</a></div></form>
@if($resource==='menus' && $record->exists) @include('admin.menu-editor') @endif
@endsection
