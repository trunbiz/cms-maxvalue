<div class="image-picker" data-image-picker>
    <input class="form-control" type="file" name="{{ $name }}" id="field-{{ $name }}" accept="image/jpeg,image/png,image/webp,image/gif" data-image-input>
    <div class="image-preview mt-3" data-image-preview @if(!$path) hidden @endif>
        <img @if($path) src="{{ media_url($path) }}" @endif data-current-src="{{ $path?media_url($path):'' }}" alt="{{ $label }} preview" data-image-thumbnail>
        <div class="small text-secondary mt-2" data-image-caption>{{ $path?'Current image':'' }}</div>
        <button type="button" class="btn btn-sm btn-link px-0" data-image-reset hidden>Undo selection</button>
    </div>
    <p class="form-text mb-0">JPG, PNG, WebP or GIF. Up to 5 MB.</p>
    <p class="small text-danger mt-2 mb-0" data-image-error role="alert" hidden></p>
</div>
