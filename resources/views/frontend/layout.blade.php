<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{{ $seo['title'] }}</title>
    <meta name="description" content="{{ $seo['description'] }}">
    <meta name="robots" content="{{ $seo['robots']??'index,follow' }}">
    @if($seo['keywords'])<meta name="keywords" content="{{ $seo['keywords'] }}">@endif
    <link rel="canonical" href="{{ $seo['canonical'] }}">
    <meta property="og:title" content="{{ $seo['title'] }}">
    <meta property="og:description" content="{{ $seo['description'] }}">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    <meta property="og:image" content="{{ $seo['image'] }}">
    <meta property="og:type" content="{{ isset($chapter)||(isset($article)&&$article instanceof \App\Models\Post)?'article':'website' }}">
    <meta property="og:locale" content="en_US">
    <meta property="og:site_name" content="{{ $settings['site_name']??'Reading Corner' }}">
    <meta name="twitter:card" content="summary_large_image">
    @if($settings['favicon']??null)<link rel="icon" href="{{ media_url($settings['favicon']) }}">@endif
    @if(preg_match('/^ca-pub-\d{16}$/',$settings['adsense_publisher_id']??''))
        <meta name="google-adsense-account" content="{{ $settings['adsense_publisher_id'] }}">
    @endif
    <script>(()=>{try{const s=JSON.parse(localStorage.getItem('reader.settings')||'{}'),r=document.documentElement;if(['light','sepia','dark'].includes(s.theme))r.dataset.theme=s.theme;if(+s.size>=16&&+s.size<=24)r.style.setProperty('--reading-size',s.size+'px');if(+s.width>=45&&+s.width<=90)r.style.setProperty('--reading-width',s.width+'ch');if(s.font==='sans')r.style.setProperty('--reading-font','"Be Vietnam Pro",sans-serif');}catch(e){}})();</script>
    @vite(['resources/css/app.css','resources/js/app.js'])
    @isset($schema)<script type="application/ld+json">{!! json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>@endisset
    @unless($isPreview??false){!! $settings['head_html']??'' !!}@endunless
</head>
<body class="reader-body">
<a class="skip-link" href="#main-content">Skip to content</a>
<div id="reading-progress" aria-hidden="true"></div>
<header class="site-header">
    <div class="container d-flex align-items-center flex-wrap gap-4 py-4">
        <a class="brand me-auto" href="/">
            @if($settings['logo']??null)<img src="{{ media_url($settings['logo']) }}" width="48" height="48" loading="lazy" alt="">@endif
            {{ $settings['site_name']??'Reading Corner' }}<span>EVERY PAGE, A NEW PERSPECTIVE</span>
        </a>
        <form action="/search" method="get" class="search-form"><input type="search" name="q" value="{{ is_string(request('q'))?request('q'):'' }}" placeholder="Search articles and stories…" aria-label="Search articles and stories"><button aria-label="Search">⌕</button></form>
    </div>
    <nav class="site-nav" aria-label="Main navigation"><div class="container"><ul class="nav gap-2">@include('frontend.menu',['items'=>$menus['main']??[]])</ul></div></nav>
</header>
<main id="main-content" class="container py-4 py-lg-5">@yield('content')</main>
<footer class="site-footer">
    <div class="container d-flex flex-wrap gap-4 justify-content-between py-5">
        <div><a class="brand" href="/">{{ $settings['site_name']??'Reading Corner' }}</a><p class="small mt-3">A quiet moment. A good story. A fresh perspective.</p><p class="small mb-0">&copy; {{ date('Y') }} {{ ($settings['publisher_name']??null)?:($settings['site_name']??'Reading Corner') }}</p></div>
        <nav aria-label="Footer navigation"><ul class="nav gap-2 footer-links">@include('frontend.menu',['items'=>$menus['footer']??[]])</ul></nav>
    </div>
</footer>
</body>
</html>
