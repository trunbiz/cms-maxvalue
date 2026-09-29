@extends('admin.layout')
@section('title','Site settings & AdSense preparation')
@section('content')
<form method="post" enctype="multipart/form-data" action="/admin/settings" class="card border-0 shadow-sm p-4">
    @csrf @method('PUT')
    <h2 class="h5">Publication identity</h2>
    <p class="text-secondary">Use your real details. Policy pages use these values when displayed; no invented publisher or contact information is supplied.</p>
    <div class="row g-4">
        @foreach(['site_name'=>'Website name','publisher_name'=>'Publisher / operator name','contact_email'=>'Public contact email','site_url'=>'Public website URL','seo_description'=>'Site description','logo'=>'Logo','favicon'=>'Favicon','adsense_publisher_id'=>'AdSense Publisher ID','ads_txt'=>'Additional ads.txt entries','head_html'=>'Custom head HTML'] as $key=>$label)
            @if($key!=='head_html'||auth()->user()->isSuperAdmin())
                <div class="{{ in_array($key,['seo_description','ads_txt','head_html'])?'col-12':'col-md-6' }}">
                    <label class="form-label" for="{{ $key }}">{{ $label }}</label>
                    @if(in_array($key,['logo','favicon']))
                        <input class="form-control" type="file" id="{{ $key }}" name="{{ $key }}" accept="image/jpeg,image/png,image/webp,image/gif">
                        @if($settings[$key]??null)<img src="{{ media_url($settings[$key]) }}" width="90" loading="lazy" alt="{{ $label }}" class="mt-2">@endif
                    @elseif(in_array($key,['seo_description','ads_txt','head_html']))
                        <textarea class="form-control" id="{{ $key }}" name="{{ $key }}" rows="{{ $key==='head_html'?7:4 }}">{{ old($key,$settings[$key]??'') }}</textarea>
                    @else
                        <input class="form-control" id="{{ $key }}" name="{{ $key }}" type="{{ $key==='contact_email'?'email':($key==='site_url'?'url':'text') }}" value="{{ old($key,$settings[$key]??'') }}" @required($key==='site_name') @if($key==='adsense_publisher_id') placeholder="ca-pub- followed by your 16 digits" @endif>
                    @endif
                    @if($key==='site_url')<small class="text-secondary">Your production HTTPS domain, for example https://your-domain.com. Set APP_URL to this domain when deploying.</small>@endif
                    @if($key==='adsense_publisher_id')<small class="text-secondary">Adds an ownership-verification meta tag and your ads.txt line. It does not load ads or submit an application.</small>@endif
                    @if($key==='ads_txt')<small class="text-secondary">The Google DIRECT line is generated from your Publisher ID. Paste other authorized sellers only.</small>@endif
                    @if($key==='head_html')<small class="text-secondary">Super Admin only. Before adding analytics or ad scripts, update the privacy notice and configure any required consent solution.</small>@endif
                </div>
            @endif
        @endforeach
        <div class="col-12">
            <input type="hidden" name="consent_reviewed" value="0">
            <label class="form-check"><input class="form-check-input" type="checkbox" name="consent_reviewed" value="1" @checked(old('consent_reviewed',$settings['consent_reviewed']??'0')==='1')><span class="form-check-label">I have reviewed the consent requirements for my visitors and configured the applicable AdSense Privacy & messaging settings or certified CMP before serving ads.</span></label>
        </div>
    </div>
    <button class="btn btn-primary align-self-start mt-4">Save settings</button>
</form>
<section class="card border-0 shadow-sm p-4 mt-4">
    <h2 class="h5">Before requesting AdSense review</h2>
    <p class="text-secondary">These checks identify missing setup. They do not assess content quality or guarantee Google approval. About, Contact, Terms, and editorial pages help readers understand the publication; they are not a guaranteed approval checklist.</p>
    <ul class="list-group list-group-flush">
        @foreach($checks as $check)
            <li class="list-group-item px-0 py-3"><div class="d-flex gap-3 align-items-start"><span class="badge {{ $check['done']?'text-bg-success':'text-bg-warning' }}">{{ $check['done']?'Set':'Review' }}</span><div><strong>{{ $check['label'] }}</strong><p class="small text-secondary mb-1">{{ $check['detail'] }}</p>@if(isset($check['edit_url']))<a class="small" href="{{ $check['edit_url'] }}">Review this page →</a>@endif</div></div></li>
        @endforeach
    </ul>
    @if(auth()->user()->hasModule('pages'))
        <form method="post" action="/admin/settings/publish-pages" class="mt-4">
            @csrf
            <label class="form-check mb-3"><input class="form-check-input" type="checkbox" name="reviewed" value="1" required><span class="form-check-label">I have reviewed and edited all six publication pages and confirm that they describe my actual identity, content, and data practices.</span></label>
            <button class="btn btn-outline-primary">Publish reviewed pages</button>
        </form>
    @endif
    <div class="d-flex flex-wrap gap-3 mt-4"><a href="/admin/posts/create" class="btn btn-primary">Write an article</a><a href="https://www.google.com/adsense/" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary">Open AdSense</a></div>
    <p class="small text-secondary mt-3 mb-0">After publishing your original content on a public domain, open AdSense → Sites → New site, verify ownership, and request review.</p>
    <p class="small mt-3 mb-0"><a href="https://support.google.com/adsense/answer/7299563?hl=en" target="_blank" rel="noopener noreferrer">Content & navigation</a> · <a href="https://support.google.com/adsense/answer/1348695?hl=en" target="_blank" rel="noopener noreferrer">Privacy disclosures</a> · <a href="https://support.google.com/adsense/answer/12169212?hl=en" target="_blank" rel="noopener noreferrer">Site verification</a> · <a href="https://support.google.com/adsense/answer/13554116?hl=en" target="_blank" rel="noopener noreferrer">Consent requirements</a></p>
</section>
@endsection
