@php($value=old($field,$field==='content' && $resource==='posts' ? $record->content?->content : $record->getAttribute($field)))
<div class="mb-4" data-field="{{ $field }}">
    @if(in_array($field,['tags','category_id','modules']))<div class="form-label">{{ $label }}</div>@else<label class="form-label" for="field-{{ $field }}">{{ $label }}</label>@endif
    @if($field==='modules')
        <div class="row g-2">@foreach(config('modules') as $key=>$module)<div class="col-sm-6"><label class="choice-option"><input class="form-check-input" type="checkbox" name="modules[]" value="{{ $key }}" @checked(in_array($key,old('modules',$record->modules??[])))><span>{{ $module }}</span></label></div>@endforeach</div>
    @elseif($field==='tags')
        <input type="hidden" name="tag_selection" value="1">
        @include('admin.fields.choices',['name'=>'tags','label'=>'Tags','options'=>$tags,'selected'=>old('tags',old('tag_selection')?[]:($record->exists?$record->tags->pluck('id')->all():[]))])
    @elseif($field==='category_id')
        <input type="hidden" name="category_selection" value="1">
        @include('admin.fields.choices',['name'=>'category_ids','label'=>'Categories','options'=>$categories,'selected'=>old('category_ids',old('category_selection')?[]:($record->exists?($record->categories->pluck('id')->all()?:array_filter([$record->category_id])):[]))])
    @elseif(in_array($field,['role_id','series_id']))
        <select class="form-select" name="{{ $field }}" id="field-{{ $field }}"><option value="">{{ $field==='series_id'?'Create a new story from the manuscript':'Select a role' }}</option>@foreach($field==='role_id'?$roles:$series as $option)<option value="{{ $option->id }}" @selected((string)$value===(string)$option->id)>{{ $option->name??$option->title }}</option>@endforeach</select>
    @elseif($field==='status')
        <select class="form-select" name="status" id="field-status"><option value="draft" @selected(($value??'published')==='draft')>Draft</option><option value="published" @selected(($value??'published')==='published')>Public</option></select>
        <p class="form-text mb-0">Public content is visible to readers. Choose Draft to keep it private.</p>
    @elseif($field==='image')
        @include('admin.fields.image',['name'=>'image','label'=>$label,'path'=>$record->image])
    @elseif(in_array($field,['content','description','excerpt','seo_description']))
        <textarea class="form-control" id="field-{{ $field }}" name="{{ $field }}" rows="{{ $field==='content'?16:4 }}" @if($field==='content') data-editor @endif>{{ $field==='content'?content_html($value):$value }}</textarea>
        @if($field==='content')<p class="form-text mb-0">Format text with the toolbar. Upload an image from your device, paste a copied image, or drag it into the editor.</p>@endif
    @else
        <input class="form-control" id="field-{{ $field }}" name="{{ $field }}" type="{{ $field==='password'?'password':($field==='published_at'?'datetime-local':'text') }}" value="{{ $field==='password'?'':($field==='published_at' && $value instanceof \Carbon\Carbon?$value->format('Y-m-d\TH:i'):$value) }}" @if($field==='password') autocomplete="new-password" @endif>
        @if($field==='password' && $record->exists)<small class="text-secondary">Leave blank to keep the current password.</small>@endif
        @if($field==='slug')<p class="form-text mb-0">Leave blank to generate from the title.</p>@endif
    @endif
    @if($field==='seo_title')<p class="form-text mb-0">Leave blank to use the title automatically.</p>@endif
    @if($field==='seo_description')<p class="form-text mb-0">Leave blank to use the description automatically.</p>@endif
    @error($field)<p class="small text-danger mt-2 mb-0">{{ $message }}</p>@enderror
</div>
