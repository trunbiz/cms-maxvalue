<div class="admin-copy-link">
    <a class="admin-public-url" href="{{ $url }}" target="_blank" rel="noopener" title="{{ $url }}">{{ $url }}</a>
    <button type="button" class="btn btn-sm btn-outline-secondary icon-button" data-copy-link="{{ $url }}" title="Copy link" aria-label="Copy link">@include('admin.icon',['name'=>'copy'])</button>
    <span class="visually-hidden" data-copy-status role="status"></span>
</div>
