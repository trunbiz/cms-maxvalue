<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminLocale
{
    public function handle(Request $request, Closure $next)
    {
        $previous = app()->getLocale();
        app()->setLocale($request->session()->get('admin_locale') ?? 'vi');
        try {
            return $next($request);
        } finally {
            app()->setLocale($previous);
        }
    }
}
