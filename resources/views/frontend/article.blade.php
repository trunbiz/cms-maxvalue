@extends('frontend.layout')
@section('content')
@php($isArticle=$article instanceof \App\Models\Post)
@if($isPreview??false)
    <div class="alert alert-info">Private preview. This page is not public and is excluded from indexing.</div>
@endif
<nav class="small mb-4" aria-label="Breadcrumb">
    <a href="/">Home</a> / @if($isArticle)<a href="/articles">Articles</a> / @endif <span>{{ $article->title }}</span>
</nav>
<article @if($isArticle && !($isPreview??false)) data-view-type="post" data-view-id="{{ $article->id }}" @endif>
    <header class="article-header text-center mb-5">
        <span class="eyebrow">{{ $isArticle?($article->category?->name??'READ & REFLECT'):'ABOUT THIS PUBLICATION' }}</span>
        <h1 class="mt-3">{{ $article->title }}</h1>
        @if($isArticle)
            @if($article->excerpt)<p class="article-deck mt-3">{{ $article->excerpt }}</p>@endif
            <div class="article-meta d-flex flex-wrap justify-content-center gap-3 mt-4">
                @if($article->published_at)<span>Published <time datetime="{{ $article->published_at->toIso8601String() }}">{{ $article->published_at->format('F j, Y') }}</time></span>@endif
                @if($article->updated_at && $article->published_at && $article->updated_at->gt($article->published_at) && !$article->updated_at->isSameDay($article->published_at))
                    <span>Updated <time datetime="{{ $article->updated_at->toIso8601String() }}">{{ $article->updated_at->format('F j, Y') }}</time></span>
                @endif
                <span>{{ $readingMinutes??1 }} min read</span>
                @unless($isPreview??false)<button type="button" class="btn btn-sm btn-outline-secondary" data-copy-link="{{ post_url($article) }}">Copy link</button>@endunless
            </div>
        @elseif($article->updated_at)
            <p class="text-muted small mt-3">Last updated <time datetime="{{ $article->updated_at->toIso8601String() }}">{{ $article->updated_at->format('F j, Y') }}</time></p>
        @endif
    </header>
    @if($article->image)
        <figure><img class="article-image mb-5" src="{{ media_url($article->image) }}" loading="lazy" alt="{{ $article->title }}"></figure>
    @endif
    <div class="reading-content article-content">{!! content_html($content) !!}</div>
    @if($isArticle)
        <div class="d-flex flex-wrap gap-2 justify-content-center mt-5">
            @foreach($article->tags as $tag)<a class="tag-pill" href="/tags/{{ $tag->slug }}">{{ $tag->name }}</a>@endforeach
        </div>
        @if($settings['contact_email']??null)
            <p class="article-feedback text-center mt-4">Have a question or correction? <a href="mailto:{{ $settings['contact_email'] }}">Contact the publisher</a>.</p>
        @endif
    @endif
</article>
@if(isset($related) && $related->isNotEmpty())
    <section class="mt-5 pt-4" aria-labelledby="related-title">
        <h2 id="related-title" class="h4 mb-4">Keep reading</h2>
        <div class="row g-4">@foreach($related as $post)<div class="col-md-4">@include('frontend.post-card')</div>@endforeach</div>
    </section>
@endif
@endsection
