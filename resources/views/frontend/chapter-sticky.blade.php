<aside class="chapter-sticky" aria-label="Reading navigation">
    <details data-chapter-sticky>
        <summary class="chapter-sticky-summary">
            <span><small>Currently reading</small><strong>Chapter {{ $chapter->chapter_number }}</strong></span>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="m6 15 6-6 6 6"/></svg>
        </summary>
        <div class="chapter-sticky-panel">
            <p class="chapter-sticky-title">{{ $chapter->title }}</p>
            <nav class="chapter-sticky-shortcuts" aria-label="Quick chapter navigation">
                @if($previous)<a href="{{ post_url($previous) }}" title="Previous chapter">&larr; Previous</a>@else<span aria-disabled="true">&larr; Previous</span>@endif
                <a href="/stories/{{ $series->slug }}">Contents</a>
                @if($next)<a href="{{ post_url($next) }}" title="Next chapter">Next &rarr;</a>@else<span aria-disabled="true">Next &rarr;</span>@endif
            </nav>
            <nav class="chapter-sticky-list" aria-label="Series chapters">
                @foreach($chapterLinks as $item)
                    <a href="{{ url('/stories/'.$series->slug.'/'.$item->slug) }}" @if($item->id === $chapter->id) aria-current="page" @endif>
                        <span class="chapter-sticky-number">{{ $item->chapter_number }}</span><span>{{ $item->title }}</span>
                    </a>
                @endforeach
            </nav>
        </div>
    </details>
</aside>
