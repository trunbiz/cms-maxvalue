<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ChapterImportController;
use App\Http\Controllers\Admin\MenuItemsController;
use App\Http\Controllers\Admin\ResourceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->middleware(\App\Http\Middleware\AdminLocale::class)->group(function () {
    Route::post('language', function (\App\Http\Requests\AdminLanguageRequest $request) {
        $request->session()->put('admin_locale', $request->validated('language'));
        return back();
    });
    Route::get('login', [AuthController::class, 'create'])->middleware('guest')->name('login');
    Route::post('login', [AuthController::class, 'store'])->middleware(['guest', 'throttle:6,1'])->name('admin.login.store');
    Route::middleware('auth')->group(function () {
        Route::get('/', [AuthController::class, 'home'])->name('admin.home');
        Route::post('logout', [AuthController::class, 'destroy'])->name('admin.logout');
        Route::get('dashboard', [ResourceController::class, 'dashboard'])->middleware('module:dashboard');
        Route::get('settings', [SettingsController::class, 'edit'])->middleware('module:settings');
        Route::put('settings', [SettingsController::class, 'update'])->middleware('module:settings');
        Route::post('posts/bulk', [ResourceController::class, 'bulk'])->middleware('module:posts');
        Route::post('upload/editor', UploadController::class);
        Route::post('upload/featured', [UploadController::class, 'featured'])->middleware('module:posts');
        Route::post('import/save', [ChapterImportController::class, 'saveDirect'])->middleware('module:posts');
        Route::get('posts/{id}/preview', [\App\Http\Controllers\Frontend\ReadingController::class, 'preview'])->middleware('module:posts')->whereNumber('id');
        Route::middleware('module:posts')->group(function () {
            Route::get('import', [ChapterImportController::class, 'create']);
            Route::post('import/preview', [ChapterImportController::class, 'preview']);
            Route::post('import', [ChapterImportController::class, 'store']);
            Route::delete('chapters/bulk-delete', [ChapterImportController::class, 'bulkDelete']);
        });
        Route::middleware(\App\Http\Middleware\ResourceModule::class)->group(function () {
            Route::get('{resource}', [ResourceController::class, 'index']);
            Route::get('{resource}/create', [ResourceController::class, 'create']);
            Route::post('{resource}', [ResourceController::class, 'store']);
            Route::get('{resource}/{id}/edit', [ResourceController::class, 'edit'])->whereNumber('id');
            Route::put('{resource}/{id}', [ResourceController::class, 'update'])->whereNumber('id');
            Route::delete('{resource}/{id}', [ResourceController::class, 'destroy'])->whereNumber('id');
        });
    });
});
