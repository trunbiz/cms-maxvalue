<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LegacyRedirectController extends Controller
{
    public function __invoke(Request $request)
    {
        $segments = explode('/', $request->path());
        $segments[0] = ['trang' => 'pages', 'danh-muc' => 'categories', 'truyen' => 'stories', 'bai-viet' => 'articles', 'tag' => 'tags', 'tim-kiem' => 'search'][$segments[0]];
        if ($segments[0] === 'pages' && isset($segments[1])) {
            $segments[1] = ['gioi-thieu' => 'about', 'lien-he' => 'contact'][$segments[1]] ?? $segments[1];
        }
        $url = '/'.implode('/', array_map('rawurlencode', $segments));
        if ($request->getQueryString()) {
            $url .= '?'.$request->getQueryString();
        }

        return redirect($url, 301);
    }
}
