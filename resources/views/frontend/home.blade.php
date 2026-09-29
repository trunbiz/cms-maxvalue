@extends('frontend.layout')
@section('content')
<section class="reading-hero mb-5">
    <div>
        <span class="eyebrow">WELCOME TO {{ $settings['site_name']??'Reading Corner' }}</span>
        <h1>Take a moment.<br>Discover a new perspective.</h1>
        <p>Make room for ideas, stories, and the pleasure of reading.<br>Find a quiet corner and explore at your own pace.</p>
        <a class="btn btn-primary px-4" href="/articles">Explore the articles <span class="ms-3">→</span></a>
    </div>
    <div class="hero-art" aria-hidden="true">
        <div class="hero-orbit"></div>
        <div class="hero-book"><span>A WORLD<br>BETWEEN<br>THE PAGES</span><div>READ & REFLECT</div></div>
        <span class="hero-note">For curious minds<br>and thoughtful readers.</span>
    </div>
</section>
<section class="mb-5" id="latest-articles">
    <div class="section-heading"><div><span class="eyebrow">READ & REFLECT</span><h2>Latest articles</h2></div><a href="/articles">View all →</a></div>
    <div class="row g-4">
        @forelse($posts as $post)<div class="col-md-6 col-lg-4">@include('frontend.post-card')</div>
        @empty<div class="col-12"><p class="text-muted">No articles have been published yet.</p></div>@endforelse
    </div>
</section>
@if($posts->isNotEmpty()||$series->isNotEmpty())
    <section class="category-strip my-5">
        <span class="eyebrow">BROWSE BY TOPIC</span>
        <div class="d-flex flex-wrap gap-3 mt-3">@foreach($categories as $category)<a class="category-pill" href="/categories/{{ $category->slug }}">{{ $category->name }} <span>↗</span></a>@endforeach</div>
    </section>
@endif
@if($series->isNotEmpty())
    <section id="new-series" class="mb-5">
        <div class="section-heading"><div><span class="eyebrow">CONTINUE THE JOURNEY</span><h2>Recently updated stories</h2></div><span class="small text-muted">Discover your next read</span></div>
        <div class="row g-4">@foreach($series as $book)<div class="col-6 col-md-4 col-lg-3">@include('frontend.series-card')</div>@endforeach</div>
    </section>
@endif
@foreach($categories as $category)
    @if(($categorySeries[$category->id]??collect())->isNotEmpty())
        <section class="mb-5">
            <div class="section-heading"><h2>{{ $category->name }}</h2><a href="/categories/{{ $category->slug }}">View all →</a></div>
            <div class="row g-4">@foreach($categorySeries[$category->id]->take(4) as $book)<div class="col-6 col-md-3">@include('frontend.series-card')</div>@endforeach</div>
        </section>
    @endif
@endforeach
@endsection
