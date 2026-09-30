<nav class="chapter-navigation" aria-label="Chapter navigation">@if($previous)
        <a class="btn btn-outline-primary" href="{{ post_url($previous) }}" data-previous>← Previous chapter</a>
    @else
        <span></span>
    @endif
{{--    <a class="btn btn-light" href="/stories/{{ $series->slug }}">Contents</a>--}}
    @if($next)
        <a class="btn btn-outline-primary" href="{{ post_url($next) }}" data-next>Next chapter →</a>
    @else
        <span></span>
    @endif</nav>
