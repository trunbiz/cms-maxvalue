@extends('frontend.layout')
@section('content')
<section class="not-found-hero text-center mb-5">
    <div class="not-found-code" aria-hidden="true">404</div>
    <h1>Content not found</h1>
    <p class="not-found-description">The content you are looking for may have been moved, removed, or is no longer available.</p>
    <p class="text-muted">Find your next read below, or search for another title.</p>
    <div class="d-flex flex-wrap justify-content-center gap-3 mt-4">
        <a class="btn btn-primary px-4" href="/">Back to home</a>
        <a class="btn btn-outline-primary px-4" href="/stories">Explore stories</a>
    </div>
</section>
@if($posts->isNotEmpty())
<section class="not-found-suggestions" aria-labelledby="suggested-title">
    <div class="section-heading"><div><span class="eyebrow">KEEP READING</span><h2 id="suggested-title">Articles you might enjoy</h2></div></div>
    <div class="row g-4">@foreach($posts as $post)<div class="col-md-6 col-lg-4">@include('frontend.post-card')</div>@endforeach</div>
</section>
@else
<p class="text-center text-muted">New articles and stories will appear here once they are published.</p>
@endif
@endsection
