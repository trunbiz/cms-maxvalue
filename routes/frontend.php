<?php

use App\Http\Controllers\Frontend\ReadingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ReadingController::class, 'home'])->name('home');
Route::get('/trang/{slug}', [ReadingController::class, 'page']);
Route::get('/danh-muc/{slug}', [ReadingController::class, 'category']);
Route::get('/tag/{slug}', [ReadingController::class, 'tag']);
Route::get('/truyen/{slug}', [ReadingController::class, 'series']);
Route::get('/truyen/{slug}/{chapterSlug}', [ReadingController::class, 'chapter']);
Route::get('/bai-viet/{slug}', [ReadingController::class, 'post']);
Route::get('/tim-kiem', [ReadingController::class, 'search']);
Route::get('/ads.txt', [ReadingController::class, 'ads']);
Route::get('/sitemap.xml', [ReadingController::class, 'sitemap'])->withoutMiddleware(\Spatie\ResponseCache\Middlewares\CacheResponse::class);
