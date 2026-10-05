<?php

use App\Http\Controllers\Frontend\ReadingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ReadingController::class, 'home'])->name('home');
Route::get('/stories', [ReadingController::class, 'stories']);
Route::get('/liferature', [ReadingController::class, 'literature']);
Route::get('/articles', [ReadingController::class, 'articles']);
Route::get('/robots.txt', [ReadingController::class, 'robots']);
Route::get('/pages/{slug}', [ReadingController::class, 'page']);
Route::get('/categories/{slug}', [ReadingController::class, 'category']);
Route::get('/tags/{slug}', [ReadingController::class, 'tag']);
Route::get('/stories/{slug}', [ReadingController::class, 'series']);
Route::get('/stories/{slug}/{chapterSlug}', [ReadingController::class, 'chapter'])->where('chapterSlug', '[a-z0-9-]+(?:/[a-z0-9-]+)?');
Route::get('/articles/{slug}', [ReadingController::class, 'post'])->where('slug', '[a-z0-9-]+(?:/[a-z0-9-]+)?');
Route::get('/search', [ReadingController::class, 'search']);
Route::get('/ads.txt', [ReadingController::class, 'ads']);
Route::get('/sitemap.xml', [ReadingController::class, 'sitemap'])->withoutMiddleware(\Spatie\ResponseCache\Middlewares\CacheResponse::class);

Route::get('/{legacy}/{remainder?}', \App\Http\Controllers\Frontend\LegacyRedirectController::class)
    ->where('legacy', 'trang|danh-muc|truyen|bai-viet|tag|tim-kiem')->where('remainder', '.*')
    ->withoutMiddleware(\Spatie\ResponseCache\Middlewares\CacheResponse::class);

Route::get('/{path}', [ReadingController::class, 'permalink'])->where('path', '(?!admin(?:/|$)|api(?:/|$))[a-z0-9%/-]+');
