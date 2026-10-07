@extends('admin.layout')
@section('title',__($definition['label']))
@section('actions')<a class="btn btn-primary d-inline-flex align-items-center gap-2" href="/admin/{{ $resource }}/create">@include('admin.icon',['name'=>'add'])<span>{{ __('Add new') }}</span></a>@endsection
@section('content')
<form method="get" class="d-flex gap-2 mb-4 {{ $resource==='posts'?'admin-post-filters flex-wrap align-items-end':'' }}">
    <input class="form-control" name="q" value="{{ request('q') }}" placeholder="{{ __('Search...') }}" aria-label="{{ __('Search') }}">
    @if(in_array($resource,['posts','series']))
    <select class="form-select" name="status"><option value="">{{ __('All statuses') }}</option>@foreach(['published'=>'Publish','draft'=>'Unpublish','bin'=>'Bin'] as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ __($label) }}</option>@endforeach</select>
    <select class="form-select" name="category_id" data-searchable-select data-search-label="{{ __('Search categories...') }}" aria-label="{{ __('All categories') }}"><option value="">{{ __('All categories') }}</option>@foreach($categories as $option)<option value="{{ $option->id }}" @selected(request('category_id')==$option->id)>{{ $option->name }}</option>@endforeach</select>
    <select class="form-select" name="created_by" data-searchable-select data-search-label="{{ __('Search creators...') }}" aria-label="{{ __('Created by') }}"><option value="">{{ __('Created by') }}</option>@foreach($authors as $option)<option value="{{ $option->id }}" @selected(request('created_by')==$option->id)>{{ $option->name }}</option>@endforeach</select>
    @if($resource==='posts')<select class="form-select" name="series_id" data-searchable-select data-search-label="{{ __('Search stories...') }}" aria-label="{{ __('All series') }}"><option value="">{{ __('All series') }}</option>@foreach($series as $option)<option value="{{ $option->id }}" @selected(request('series_id')==$option->id)>{{ $option->title }}</option>@endforeach</select>@endif
    @endif
    @if($resource==='posts')
        <div class="admin-date-filter"><label class="form-label mb-1" for="created-from">{{ __('Created from') }}</label><input type="date" class="form-control" id="created-from" name="created_from" value="{{ request('created_from') }}"></div>
        <div class="admin-date-filter"><label class="form-label mb-1" for="created-until">{{ __('Created until') }}</label><input type="date" class="form-control" id="created-until" name="created_until" value="{{ request('created_until') }}"></div>
    @endif
    <button class="btn btn-outline-primary">{{ __('Search') }}</button>
</form>
@if($resource==='posts')
<form id="bulkPosts" action="/admin/posts/bulk" method="post" class="mb-3" data-bulk-posts>
@csrf
<div data-bulk-actions hidden class="d-flex align-items-center flex-wrap gap-2 admin-posts-bulk">
<span data-selected-count></span>
<button name="action" value="published" class="btn btn-success">Publish</button>
<button name="action" value="draft" class="btn btn-outline-secondary">Unpublish</button>
<button name="action" value="bin" class="btn btn-outline-danger">Move to bin</button>
</div>
</form>
@endif
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0 {{ $resource==='posts'?'admin-posts-table':'' }}">
    <thead><tr>@if($resource==='posts')<th class="post-selection-cell"><label class="post-selection-target"><input type="checkbox" class="form-check-input" data-select-all aria-label="Select all posts on this page"></label></th>@endif<th>#</th><th>{{ __('Name / Title') }}</th>@if(in_array($resource,['posts','series','pages']))<th>{{ __('Status') }}</th>@endif @if(in_array($resource,['series','tags']))<th>{{ __($resource==='series'?'Chapters':'Articles') }}</th>@endif @if($resource==='posts')<th>{{ __('Created at') }}</th><th>{{ __('Created by') }}</th>@endif<th class="text-end">{{ __('Actions') }}</th></tr></thead>
    <tbody>
        @forelse($records as $record)
            <tr>
                @if($resource==='posts')<td class="post-selection-cell"><label class="post-selection-target"><input type="checkbox" class="form-check-input" form="bulkPosts" name="ids[]" value="{{ $record->id }}" data-select-post aria-label="Select {{ $record->title }}"></label></td>@endif
                <td>{{ $resource==='posts' && request('series_id') ? $record->chapter_number : $record->id }}</td>
                <td><a class="fw-medium" href="/admin/{{ $resource }}/{{ $record->id }}/edit">@if($resource==='posts' && $record->type==='chapter'){{ __('Chapter :number', ['number'=>$record->chapter_number]) }}: @endif{{ $record->title??$record->name }}</a>@if($resource==='posts' && $record->series)<small class="d-block text-secondary">{{ $record->series->title }}</small>@endif</td>
                @if(in_array($resource,['posts','series','pages']))<td><span class="badge {{ $record->status==='published'?'text-bg-success':'text-bg-secondary' }}">{{ __($record->status==='published'?'Publish':($record->status==='bin'?'Bin':'Unpublish')) }}</span></td>@endif
                @if(in_array($resource,['series','tags']))<td>{{ $record->chapters_count??$record->posts_count }}</td>@endif
                @if($resource==='posts')
                    <td class="post-created-at">@if($record->created_at)<time datetime="{{ $record->created_at->toIso8601String() }}">{{ $record->created_at->format('d/m/Y') }}<span class="d-block text-secondary mt-1">{{ $record->created_at->format('H:i') }}</span></time>@else<span class="text-secondary">&mdash;</span>@endif</td>
                    <td class="post-created-by">{{ $record->creator?->name ?? '—' }}</td>
                @endif
                <td><div class="d-flex gap-2 justify-content-end">
                    @if($resource==='series')<a class="btn btn-sm btn-outline-primary icon-button" title="{{ __('Chapters') }}" aria-label="{{ __('Chapters') }}" href="/admin/posts?series_id={{ $record->id }}">@include('admin.icon',['name'=>'chapters'])</a>@endif
                    @if($resource==='posts')@include('admin.copy-link',['url'=>post_url($record)])@endif
                    @if($resource==='pages')@include('admin.copy-link',['url'=>url('/pages/'.$record->slug)])@endif
                    <a class="btn btn-sm btn-light btn-outline-secondary icon-button" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}" href="/admin/{{ $resource }}/{{ $record->id }}/edit">@include('admin.icon',['name'=>'edit'])</a>
                    <form method="post" action="/admin/{{ $resource }}/{{ $record->id }}" data-confirm>@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger icon-button" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">@include('admin.icon',['name'=>'delete'])</button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="{{ $resource==='posts'?7:(in_array($resource,['series','pages'])?($resource==='series'?5:4):($resource==='tags'?4:3)) }}" class="text-center py-5 text-secondary">{{ __('No items yet.') }}</td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="mt-4">{{ $records->links() }}</div>
@endsection
