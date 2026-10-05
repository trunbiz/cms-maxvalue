<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ResourceModule
{
    public function handle(Request $request, Closure $next)
    {
        $resource = $request->route('resource');
        abort_if(in_array($resource, ['menus', 'pages'], true), 404);
        abort_unless(config('cms.resources.'.$resource), 404);
        abort_unless($request->user()?->hasModule($resource === 'series' ? 'posts' : $resource), 403);

        return $next($request);
    }
}
