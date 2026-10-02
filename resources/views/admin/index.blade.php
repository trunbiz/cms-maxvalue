@extends('admin.layout')
@section('title',__($definition['label']))
@section('actions')<a class="btn btn-primary icon-button" title="{{ __('Add new') }}" aria-label="{{ __('Add new') }}" href="/admin/{{ $resource }}/create">@include('admin.icon',['name'=>'add'])</a>@endsection
@section('content')
<form method="get" class="d-flex gap-2 mb-4">
    <input class="form-control" name="q" value="{{ request('q') }}" placeholder="{{ __('Search...') }}" aria-label="{{ __('Search') }}">
    @if(in_array($resource,['posts','series']))
    <select class="form-select" name="status"><option value="">{{ __('All statuses') }}</option>@foreach(['published'=>'Publish','draft'=>'Unpublish','bin'=>'Bin'] as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ __($label) }}</option>@endforeach</select>
    <select class="form-select" name="category_id"><option value="">{{ __('All categories') }}</option>@foreach($categories as $option)<option value="{{ $option->id }}" @selected(request('category_id')==$option->id)>{{ $option->name }}</option>@endforeach</select>
    <select class="form-select" name="created_by"><option value="">{{ __('Created by') }}</option>@foreach($authors as $option)<option value="{{ $option->id }}" @selected(request('created_by')==$option->id)>{{ $option->name }}</option>@endforeach</select>
    @if($resource==='posts')<select class="form-select" name="series_id"><option value="">{{ __('All series') }}</option>@foreach($series as $option)<option value="{{ $option->id }}" @selected(request('series_id')==$option->id)>{{ $option->title }}</option>@endforeach</select>@endif
    @endif
    <button class="btn btn-outline-primary">{{ __('Search') }}</button>
</form>
@if($resource==='posts' && request('series_id'))
    <form id="bulkDelete" action="/admin/chapters/bulk-delete" method="post" data-confirm>@csrf @method('DELETE')<input type="hidden" name="series_id" value="{{ request('series_id') }}"><button class="btn btn-outline-danger mb-3">{{ __('Delete selected chapters') }}</button></form>
@endif
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>#</th><th>{{ __('Name / Title') }}</th>@if(in_array($resource,['posts','series','pages']))<th>{{ __('Status') }}</th>@endif @if(in_array($resource,['series','tags']))<th>{{ __($resource==='series'?'Chapters':'Articles') }}</th>@endif<th class="text-end">{{ __('Actions') }}</th></tr></thead>
    <tbody>
        @forelse($records as $record)
            <tr>
                <td>@if($resource==='posts' && request('series_id'))<input type="checkbox" class="form-check-input me-2" form="bulkDelete" name="ids[]" value="{{ $record->id }}" aria-label="Select chapter {{ $record->chapter_number }}">{{ $record->chapter_number }}@else{{ $record->id }}@endif</td>
                <td><a class="fw-medium" href="/admin/{{ $resource }}/{{ $record->id }}/edit">{{ $record->title??$record->name }}</a>@if($resource==='posts' && $record->series)<small class="d-block text-secondary">{{ $record->series->title }}</small>@endif</td>
                @if(in_array($resource,['posts','series','pages']))<td><span class="badge {{ $record->status==='published'?'text-bg-success':'text-bg-secondary' }}">{{ __($record->status==='published'?'Publish':($record->status==='bin'?'Bin':'Unpublish')) }}</span></td>@endif
                @if(in_array($resource,['series','tags']))<td>{{ $record->chapters_count??$record->posts_count }}</td>@endif
                <td><div class="d-flex gap-2 justify-content-end">
                    @if($resource==='series')<a class="btn btn-sm btn-outline-primary icon-button" title="{{ __('Chapters') }}" aria-label="{{ __('Chapters') }}" href="/admin/posts?series_id={{ $record->id }}">@include('admin.icon',['name'=>'chapters'])</a>@endif
                    @if($resource==='posts')@include('admin.copy-link',['url'=>post_url($record)])@endif
                    <a class="btn btn-sm btn-light icon-button" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}" href="/admin/{{ $resource }}/{{ $record->id }}/edit">@include('admin.icon',['name'=>'edit'])</a>
                    <form method="post" action="/admin/{{ $resource }}/{{ $record->id }}" data-confirm>@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger icon-button" title="{{ __('Delete') }}" aria-label="{{ __('Delete') }}">@include('admin.icon',['name'=>'delete'])</button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center py-5 text-secondary">{{ __('No items yet.') }}</td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="mt-4">{{ $records->links() }}</div>
@endsection
