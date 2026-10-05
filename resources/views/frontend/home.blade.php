@extends('frontend.layout')
@section('content')
<section class="mb-5" id="latest-chapters">
    <div class="section-heading"><div><span class="eyebrow">READ & REFLECT</span><h2>Latest chapters</h2></div><a href="/stories">View all →</a></div>
    <div class="row g-4">
        @forelse($posts as $post)<div class="col-md-6 col-lg-4">@include('frontend.chapter-card')</div>
        @empty<div class="col-12"><p class="text-muted">No chapters have been published yet.</p></div>@endforelse
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
