<?php

use Illuminate\Support\Facades\Route;

Route::post('/views', \App\Http\Controllers\Frontend\ViewController::class)->middleware('throttle:120,1');
