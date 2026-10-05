<article class="post-card">
    <a href="{{ post_url($post) }}"><img src="{{ media_url($post->image) }}" loading="lazy" alt="{{ $post->title }}" width="360" height="220"></a>
    <div class="pt-3">
        <a class="eyebrow" href="/stories/{{ $post->series->slug }}">{{ $post->series->title }}</a>
        <h3 class="h5 mt-2"><a href="{{ post_url($post) }}">{{ $post->title }}</a></h3>
        <p class="small text-muted">Chapter {{ $post->chapter_number }}</p>
    </div>
</article>
