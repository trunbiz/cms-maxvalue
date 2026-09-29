@extends('admin.layout')
@section('title','Review chapters')
@section('content')
<section class="editor-section">
    @include('admin.chapter-preview')
    <form method="post" action="/admin/import" class="d-flex gap-2 mt-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <button class="btn btn-primary">Save chapters</button>
        <a href="/admin/posts/create?mode=import" class="btn btn-light">Back to manuscript</a>
    </form>
</section>
@endsection
