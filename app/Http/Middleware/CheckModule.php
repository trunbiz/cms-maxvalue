<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckModule
{
    public function handle(Request $request, Closure $next, string $module)
    {
        abort_unless($request->user()?->hasModule($module), 403, 'Bạn không có quyền truy cập.');

        return $next($request);
    }
}
