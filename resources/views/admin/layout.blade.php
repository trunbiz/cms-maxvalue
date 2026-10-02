<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex,nofollow">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','Admin') · Reading Corner</title>@vite(['resources/css/app.css','resources/js/app.js'])
    <script>window.adminTranslations = @json(app()->getLocale() === 'vi' ? json_decode(file_get_contents(lang_path('vi.json')), true) : []);</script>
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="offcanvas-lg offcanvas-start admin-sidebar" tabindex="-1" id="sidebar">
        <div class="offcanvas-header"><h5>Reading Corner CMS</h5>
            <button class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebar"
                    aria-label="{{ __('Close') }}"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column"><a class="admin-brand" href="/admin">Reading
                Corner<span>{{ __('PUBLISHING WORKSPACE') }}</span></a>
            <nav class="nav flex-column gap-1 mt-4" aria-label="{{ __('Admin navigation') }}">
                @php($navigationGroups = [
                    'Publishing' => ['posts' => 'Articles & chapters', 'series' => 'Series', 'pages' => 'Pages'],
                    'Organization' => ['categories' => 'Categories', 'tags' => 'Tags'],
                    'Website' => ['dashboard' => 'Dashboard', 'menus' => 'Menus', 'settings' => 'Settings'],
                    'Access management' => ['users' => 'Users', 'roles' => 'Roles'],
                ])
                @foreach($navigationGroups as $group => $links)
                    @if(collect(array_keys($links))->contains(fn($module) => auth()->user()->hasModule($module === 'series' ? 'posts' : $module)))
                        <div class="admin-nav-group">
                            <div class="admin-nav-heading">{{ __($group) }}</div>
                            @if($group === 'Publishing' && auth()->user()->hasModule('posts'))
                                <a class="nav-link admin-compose-link {{ request()->is('admin/posts/create')?'active':'' }}"
                                   href="/admin/posts/create">@include('admin.icon',['name'=>'add'])
                                    <span>{{ __('Publish content') }}</span></a>
                            @endif
                            @foreach($links as $module => $label)
                                @if(auth()->user()->hasModule($module === 'series' ? 'posts' : $module))
                                    @php($active = $module === 'dashboard' ? request()->is('admin', 'admin/dashboard') : (request()->is('admin/'.$module, 'admin/'.$module.'/*') && !($module === 'posts' && request()->is('admin/posts/create'))))
                                    <a class="nav-link {{ $active?'active':'' }}"
                                       href="{{ $module === 'dashboard' ? '/admin' : '/admin/'.$module }}"
                                       @if($active) aria-current="page" @endif>{{ __($label) }}</a>
                                @endif
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </nav>
{{--            <a href="/" class="mt-auto pt-5 text-white-50">{{ __('View website ↗') }}</a></div>--}}
    </aside>
    <div class="admin-main">
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="btn btn-outline-secondary d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#sidebar"
                    aria-label="{{ __('Open menu') }}">☰
            </button>
            <span class="small text-secondary">CMS / @yield('title','Dashboard')</span>
            <div class="d-flex align-items-center gap-3 ms-auto">
                <form method="post" action="/admin/language">@csrf<label class="visually-hidden" for="admin-language">Language</label><select
                        id="admin-language" name="language" class="form-select form-select-sm"
                        onchange="this.form.requestSubmit()">
                        <option value="vi" @selected(app()->getLocale()==='vi')>Tiếng Việt</option>
                        <option value="en" @selected(app()->getLocale()==='en')>English</option>
                    </select></form>
                <span>{{ auth()->user()->name }}</span>
                <form action="{{ route('admin.logout') }}" method="post">@csrf
                    <button class="btn btn-sm btn-outline-secondary">{{ __('Sign out') }}</button>
                </form>
            </div>
        </header>
        <main class="container-fluid p-3 p-lg-5">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4"><h1
                    class="h3 mb-0">@yield('title','Dashboard')</h1>@yield('actions')</div>@if($errors->any())
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">@foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach</ul>
                </div>
            @endif @yield('content')</main>
    </div>
</div>
@if(session('success'))
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast text-bg-success" role="status" data-auto-toast>
            <div class="d-flex">
                <div class="toast-body">{{ __(session('success')) }}</div>
                <button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="{{ __('Close') }}"></button>
            </div>
        </div>
    </div>
@endif
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteTitle">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h2 class="modal-title fs-5" id="deleteTitle">{{ __('Confirm deletion') }}</h2>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body">{{ __('Delete the selected items? This action cannot be undone.') }}</div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                <button class="btn btn-danger" id="confirmDelete">{{ __('Delete items') }}</button>
            </div>
        </div>
    </div>
</div>
</body>
</html>
