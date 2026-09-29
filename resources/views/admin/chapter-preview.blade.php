<div class="d-flex flex-wrap justify-content-between gap-2 align-items-start mb-3"><div><span class="eyebrow">Ready to review</span><h2 class="h4 mt-2">{{ $preview['title'] }}</h2></div><span class="badge text-bg-light border">{{ count($preview['chapters']) }} chapters</span></div>
@if($preview['description'])<p class="text-secondary" style="white-space:pre-line">{{ $preview['description'] }}</p>@endif
<p class="small text-secondary">Save as: <strong>{{ $options['status']==='published'?'Published':'Draft' }}</strong>. Existing chapter numbers: {{ $options['duplicates']==='skip'?'keep existing':'replace' }}.</p>
@if($coverImage??null)
    <figure class="image-preview mb-3"><img src="{{ media_url($coverImage) }}" alt="Shared chapter cover"><figcaption class="small text-secondary mt-2">This cover will be used for all imported chapters.</figcaption></figure>
@elseif(!empty($options['share_image']))
    <p class="alert alert-warning small">No cover image is available. Select a featured image or add a cover to the selected story, then analyze again.</p>
@endif
@if($preview['warnings'])<div class="alert alert-warning"><ul class="mb-0">@foreach($preview['warnings'] as $warning)<li>{{ $warning }}</li>@endforeach</ul></div>@endif
<p class="small text-secondary">Open each chapter to review its complete content. To change the text, edit the manuscript and analyze again.</p>
@foreach($preview['chapters'] as $chapter)
    <details class="chapter-preview" @if($loop->first) open @endif>
        <summary><span class="chapter-preview-number">{{ $chapter['number'] }}</span><span class="flex-grow-1">{{ $chapter['title'] }}<small class="d-block text-secondary fw-normal">{{ $chapter['words'] }} words @if($chapter['existing']) · {{ $options['duplicates']==='skip'?'Will be skipped':'Will replace existing chapter' }} @else · New chapter @endif</small></span></summary>
        <div class="chapter-preview-content ck-content">{!! content_html(clean_html($chapter['content'])) !!}</div>
    </details>
@endforeach
