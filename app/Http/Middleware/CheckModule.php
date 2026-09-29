<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckModule
{
    public function handle(Request $request, Closure $next, string $module)
    {
        abort_unless($request->user()?->hasModule($module), 403, 'You do not have permission to access this resource.');

        return $next($request);
    }
}
