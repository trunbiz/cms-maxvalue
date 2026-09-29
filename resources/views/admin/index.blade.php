@extends('admin.layout')
@section('title',$definition['label'])
@section('actions')<a class="btn btn-primary" href="/admin/{{ $resource }}/create">+ Add new</a>@endsection
@section('content')
<form method="get" class="d-flex gap-2 mb-4">
    <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search..." aria-label="Search">
    @if(request('series_id'))<input type="hidden" name="series_id" value="{{ request('series_id') }}">@endif
    <button class="btn btn-outline-primary">Search</button>
</form>
@if($resource==='posts' && request('series_id'))
    <form id="bulkDelete" action="/admin/chapters/bulk-delete" method="post" data-confirm>@csrf @method('DELETE')<input type="hidden" name="series_id" value="{{ request('series_id') }}"><button class="btn btn-outline-danger mb-3">Delete selected chapters</button></form>
@endif
<div class="card border-0 shadow-sm"><div class="table-responsive"><table class="table align-middle mb-0">
    <thead><tr><th>#</th><th>Name / Title</th>@if(in_array($resource,['posts','series','pages']))<th>Status</th>@endif @if(in_array($resource,['series','tags']))<th>{{ $resource==='series'?'Chapters':'Articles' }}</th>@endif<th class="text-end">Actions</th></tr></thead>
    <tbody>
        @forelse($records as $record)
            <tr>
                <td>@if($resource==='posts' && request('series_id'))<input type="checkbox" class="form-check-input me-2" form="bulkDelete" name="ids[]" value="{{ $record->id }}" aria-label="Select chapter {{ $record->chapter_number }}">{{ $record->chapter_number }}@else{{ $record->id }}@endif</td>
                <td><a class="fw-medium" href="/admin/{{ $resource }}/{{ $record->id }}/edit">{{ $record->title??$record->name }}</a>@if($resource==='posts' && $record->series)<small class="d-block text-secondary">{{ $record->series->title }}</small>@endif</td>
                @if(in_array($resource,['posts','series','pages']))<td><span class="badge {{ $record->status==='published'?'text-bg-success':'text-bg-secondary' }}">{{ $record->status==='published'?'Published':'Draft' }}</span></td>@endif
                @if(in_array($resource,['series','tags']))<td>{{ $record->chapters_count??$record->posts_count }}</td>@endif
                <td><div class="d-flex gap-2 justify-content-end">
                    @if($resource==='series')<a class="btn btn-sm btn-outline-primary text-nowrap" href="/admin/posts?series_id={{ $record->id }}">Chapters</a>@endif
                    @if($resource==='posts')<button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" data-copy-link="{{ post_url($record) }}" title="{{ $record->status==='draft'?'Public link becomes available after publishing':'Copy the public article link' }}">Copy link</button>@endif
                    <a class="btn btn-sm btn-light" href="/admin/{{ $resource }}/{{ $record->id }}/edit">Edit</a>
                    <form method="post" action="/admin/{{ $resource }}/{{ $record->id }}" data-confirm>@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                </div></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center py-5 text-secondary">No items yet.</td></tr>
        @endforelse
    </tbody>
</table></div></div>
<div class="mt-4">{{ $records->links() }}</div>
@endsection
