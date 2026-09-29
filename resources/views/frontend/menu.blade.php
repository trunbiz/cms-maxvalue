@foreach($items as $item)
<li class="nav-item {{ $item['children']?'has-submenu':'' }}">
    <a class="nav-link" href="{{ $item['href'] }}">{{ $item['label'] }}</a>
    @if($item['children'])
        <button type="button" class="submenu-toggle" aria-label="Open submenu {{ $item['label'] }}" aria-expanded="false">⌄</button>
        <ul class="submenu list-unstyled">@include('frontend.menu',['items'=>$item['children']])</ul>
    @endif
</li>
@endforeach
