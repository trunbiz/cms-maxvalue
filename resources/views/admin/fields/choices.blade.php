@php($selected = array_map('strval', (array) $selected))
<div class="choice-picker" role="group" aria-label="{{ __($label) }}" data-choice-picker @if($name==='tags') data-tag-picker @endif>
    <input type="search" class="form-control form-control-sm" placeholder="{{ __('Search') }} {{ mb_strtolower(__($label)) }}..." aria-label="{{ __('Search') }} {{ mb_strtolower(__($label)) }}" data-choice-search>
    <div class="small text-secondary my-2" data-choice-summary aria-live="polite"></div>
    <div class="choice-list" data-choice-list>
        @foreach($options as $option)
            <label class="choice-option"><input class="form-check-input" type="checkbox" name="{{ $name }}[]" value="{{ $option->id }}" @checked(in_array((string)$option->id,$selected,true))><span>{{ $option->name??$option->title }}</span></label>
        @endforeach
        @if($name==='tags')
            @foreach(array_filter($selected,fn($id)=>str_starts_with($id,'new:')) as $tag)
                <label class="choice-option"><input class="form-check-input" type="checkbox" name="tags[]" value="{{ $tag }}" checked><span>{{ substr($tag,4) }}</span></label>
            @endforeach
        @endif
    </div>
    <p class="small text-secondary mb-0 mt-2" data-choice-empty hidden>{{ __('No matches found.') }}</p>
    @if($name==='tags')
        <div class="input-group input-group-sm mt-3"><input class="form-control" data-new-tag placeholder="{{ __('Create a tag') }}" aria-label="{{ __('New tag name') }}" maxlength="96"><button type="button" class="btn btn-outline-primary" data-add-tag>{{ __('Add') }}</button></div>
    @endif
</div>
