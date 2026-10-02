@extends('admin.layout')
@section('title',__('Site settings'))
@section('content')
<form method="post" enctype="multipart/form-data" action="/admin/settings" class="card border-0 shadow-sm p-4">
    @csrf @method('PUT')
    <h2 class="h5">{{ __('Publication identity') }}</h2>
    <p class="text-secondary">{{ __('Use your real details. Policy pages use these values when displayed; no invented publisher or contact information is supplied.') }}</p>
    <div class="row g-4">
        @foreach(['site_name'=>'Website name','publisher_name'=>'Publisher / operator name','contact_email'=>'Public contact email','site_url'=>'Public website URL','seo_description'=>'Site description','logo'=>'Logo','favicon'=>'Favicon','head_html'=>'Custom head HTML'] as $key=>$label)
            @if($key!=='head_html'||auth()->user()->isSuperAdmin())
                <div class="{{ in_array($key,['seo_description','head_html'])?'col-12':'col-md-6' }}">
                    <label class="form-label" for="{{ in_array($key,['logo','favicon'])?'field-'.$key:$key }}">{{ __($label) }}</label>
                    @if(in_array($key,['logo','favicon']))
                        @include('admin.fields.image',['name'=>$key,'label'=>$label,'path'=>$settings[$key]??null])
                    @elseif(in_array($key,['seo_description','head_html']))
                        <textarea class="form-control" id="{{ $key }}" name="{{ $key }}" rows="{{ $key==='head_html'?7:4 }}">{{ old($key,$settings[$key]??'') }}</textarea>
                    @else
                        <input class="form-control" id="{{ $key }}" name="{{ $key }}" type="{{ $key==='contact_email'?'email':($key==='site_url'?'url':'text') }}" value="{{ old($key,$settings[$key]??'') }}" @required($key==='site_name')>
                    @endif
                    @if($key==='site_url')<small class="text-secondary">{{ __('Your production HTTPS domain, for example https://your-domain.com. Set APP_URL to this domain when deploying.') }}</small>@endif
                    @if($key==='head_html')<small class="text-secondary">{{ __('Super Admin only. Before adding analytics or ad scripts, update the privacy notice and configure any required consent solution.') }}</small>@endif
                </div>
            @endif
        @endforeach
        <div class="col-12">
            <input type="hidden" name="consent_reviewed" value="0">
            <label class="form-check"><input class="form-check-input" type="checkbox" name="consent_reviewed" value="1" @checked(old('consent_reviewed',$settings['consent_reviewed']??'0')==='1')><span class="form-check-label">{{ __('I have reviewed the consent requirements for my visitors and configured the applicable AdSense Privacy & messaging settings or certified CMP before serving ads.') }}</span></label>
        </div>
    </div>
    <button class="btn btn-primary align-self-start mt-4">{{ __('Save settings') }}</button>
</form>
@endsection
