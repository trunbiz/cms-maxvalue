@foreach($items as $item)
@php
    $menuPath = '/'.trim(parse_url($item['href'], PHP_URL_PATH) ?: '/', '/');
    $currentPath = $activeMenu ?? '/'.trim(request()->path(), '/');
    $menuHost = parse_url($item['href'], PHP_URL_HOST);
    $isLocal = ! $menuHost || $menuHost === request()->getHost();
    $isActive = $isLocal && ($currentPath === $menuPath || ($menuPath !== '/' && str_starts_with($currentPath, $menuPath.'/')));
@endphp
<li class="nav-item {{ $item['children']?'has-submenu':'' }}">
    <a class="nav-link{{ $isActive?' active':'' }}" href="{{ $item['href'] }}" @if($isActive)aria-current="{{ '/'.trim(request()->path(), '/') === $menuPath && !request()->has('p') ? 'page' : 'location' }}"@endif>{{ $item['label'] }}</a>
    @if($item['children'])
        <button type="button" class="submenu-toggle" aria-label="Open submenu {{ $item['label'] }}" aria-expanded="false">⌄</button>
        <ul class="submenu list-unstyled">@include('frontend.menu',['items'=>$item['children']])</ul>
    @endif
</li>
@endforeach
